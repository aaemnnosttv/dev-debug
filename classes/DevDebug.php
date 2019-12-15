<?php

use DevDebug\Capture;
use DevDebug\DebugBar\CapturesPanel;
use DevDebug\DebugBar\LogPanel;
use DevDebug\Logger;

/**
 * @package DevDebug
 * @author  Evan Mattson @aaemnnosttv
 */

class DevDebug
{

	const slug = 'dev-debug';

	/**
	 * [$instance description]
	 * @var [type]
	 */
	private static $instance;
	/**
	 * [$logger description]
	 * @var [type]
	 */
	private $logger;
	/**
	 * @var string
	 */
	public $log_filepath;
	/**
	 * [$captured description]
	 * @var array
	 */
	private $captured = array();

	/**
	 * [$did_styles description]
	 * @var [type]
	 */
	private $did_styles;

	/**
	 * Sets the minimum level to log
	 *
	 * const DEBUG	= 1;	// Most Verbose
	 * const INFO	= 2;	// ...
	 * const WARN	= 3;	// ...
	 * const ERROR	= 4;	// ...
	 * const FATAL	= 5;	// Least Verbose
	 * const OFF	= 6;	// Nothing at all.
	 */
	public static $log_level = Logger::INFO;




	public static function get_instance()
	{
		if ( is_null( self::$instance ) )
			self::$instance = new self();

		return self::$instance;
	}

	private function __construct()
	{
		/**
		 * Set the directory for to write debug log file to
		 * filter 'ddbug/logging/path'
		 */
		$log_dir = apply_filters( 'ddbug/logging/path', WP_CONTENT_DIR );
		$this->log_filepath = path_join( $log_dir, '.htdev-debug.log' );
		$this->logger = new Logger( $this->log_filepath, self::$log_level );
	}

	public function register()
	{
		add_action( 'shutdown', array($this, 'output_captured') );
		add_filter( 'debug_bar_panels', array($this, 'init_debug_bar_panels') );

		do_action( 'ddbug/ready', $this );
	}

	public function init_debug_bar_panels( $panels )
	{
		array_unshift( $panels, new LogPanel( 'DevDebug Log' ) );
		array_unshift( $panels, new CapturesPanel( 'DevDebug Captures' ) );

		return $panels;
	}

	function print_styles()
	{
		if (! $this->did_styles && ! $this->suppress_output_captured()) {
			printf('<style id="dev-debug-style" type="text/css">%s</style>',
				file_get_contents(DEVDEBUG_DIR . '/assets/dist/dev-debug.min.css')
			);
			$this->did_styles = true;
		}
	}

	public function analyze( $data, $args = array() )
	{
		// maybe record this
		$this->log( $data, __METHOD__, Logger::DEBUG );

		$d = array(
			'backtrace'  => array(),
		);
		$args = wp_parse_args( $args, $d );

		$datatype = gettype( $data );
		if ( !isset( $args['title'] ) ) {
			if ( is_array( $data ) ) {
				$count = count($data);
				$args['title'] = "array[$count]";
			} elseif ( is_object( $data ) ) {
				$class = get_class( $data );
				$args['title'] = "$datatype ( $class )";
			}
			else
				$args['title'] = $datatype;
		}

		extract( $args );

		$args['data'] = $data;

		$this->captured[] = new Capture($args);

		// always return data - unless explicitly returning markup
		// allows inline / "chainable" usage
		return $data;
	}

	public function has_captures()
	{
		return ! empty($this->captured);
	}

	/**
	 * Shutdown callback
	 */
	function output_captured()
	{
		if ($this->suppress_output_captured()) {
			return;
		}

		$captures = $this->captured;
		include DEVDEBUG_DIR . '/src/views/all-captures.php';

		$this->print_styles();
	}

	/**
	 * Determine if anything should be output at shutdown
	 *
	 * @todo	add logic for detecting non-standard page loads
	 * 			ie: generated styles/js, robots.txt, etc.
	 */
	function suppress_output_captured()
	{
		$suppress = false;

		if ( headers_sent() )
		{
			foreach( headers_list() as $header )
			{
				if ( 0 === stripos($header, 'Content-type:') )
				{
					// a content-type header has been sent

					if ( false === stripos($header,'text/html') )
					{
						$suppress = true;
						$this->log('output suppressed: non-html content-type request', __METHOD__, Logger::DEBUG);
					}

					break;
				}
			}
		}

		if ( empty( $this->captured ) )
		{
			$this->log('nothing captured', __METHOD__, Logger::DEBUG);
			$suppress = true;
		}
		elseif ( wp_doing_ajax() )
		{
			$this->log('output suppressed: doing ajax', __METHOD__, Logger::DEBUG);
			$suppress = true;
		}
		elseif ( PHP_SAPI == 'cli' )
		{
			$this->log('output suppressed: cli', __METHOD__, Logger::DEBUG);
			$suppress = true;
		}
		elseif ( $this->is_screen_id('async-upload') )
		{
			$this->log('output suppressed: media upload', __METHOD__, Logger::DEBUG);
			$suppress = true;
		}
		elseif ( apply_filters( 'ddbug/output/footer/suppress', false ) )
		{
			$this->log('output suppressed: filter', __METHOD__, Logger::DEBUG);
			$suppress = true;
		}

		return $suppress;
	}

	protected function is_screen_id($id)
	{
		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		return $id === $screen->id;
	}

	/**
	 * Get the value of a constant without regard to its existence
	 *
	 * @param  string $c constant name
	 * @return mixed    constant value
	 */
	public static function const_value( $c )
	{
		$defined = defined( $c );
		$value   = $defined ? constant( $c ) : null;
		return $value;
	}

	public static function get_realm()
	{
		if (wp_doing_cron()) {
			return 'cron';
		}

		if (wp_doing_ajax()) {
			return 'ajax';
		}

		if (self::const_value('REST_REQUEST')) {
			return 'rest';
		}

		return is_admin() ? 'admin' : 'front';
	}

	/**
	 * Low-level debugging
	 *
	 * For testing our own doings
	 *
	 * @param      $msg
	 * @param null $title
	 * @param null $level
	 *
	 * @return void [type] [description]
	 */
	public function log( $msg, $title = null, $level = null )
	{
		if ( is_null( $level ) )
			$level = DevDebug::$log_level;

		$log = sprintf( '(%s)', self::get_realm() );
		$log .= is_scalar( $title ) ? "[$title]" : '';

		if ( is_scalar( $msg ) )
			$log .= " $msg";
		else
		{
			$dump = print_r( $msg, true );
			$log .= "\n$dump";
			$log .= str_repeat('-----', 10);
		}

		$this->logger->Log( $log, $level );
	}

}
