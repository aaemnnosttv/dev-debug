<?php

use DevDebug\Capture;
use DevDebug\DebugBar\CapturesPanel;
use DevDebug\DebugBar\LogPanel;

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
	private static $logger;
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
	 * Sets the minimun level to log
	 *
	 * const DEBUG	= 1;	// Most Verbose
	 * const INFO	= 2;	// ...
	 * const WARN	= 3;	// ...
	 * const ERROR	= 4;	// ...
	 * const FATAL	= 5;	// Least Verbose
	 * const OFF	= 6;	// Nothing at all.
	 */
	public static $log_level = DevDebug_Logger::INFO;




	public static function get_instance()
	{
        if ( is_null( self::$instance ) )
            self::$instance = new self();

        return self::$instance;
	}

	private function __construct()
	{
		// load internal logging
		//require_once "DevDebug_Logger.php";
		$this->dir = dirname( dirname( __FILE__ ) );
		$this->uri = plugins_url( '', "$this->dir/dev-debug.php" );

		/**
		 * Set the directory for to write debug log file to
		 * filter 'ddbug/logging/path'
		 */
		$log_dir = apply_filters( 'ddbug/logging/path', WP_CONTENT_DIR );
		$this->log_filepath = path_join( $log_dir, '.htdev-debug.log' );
		self::$logger = new DevDebug_Logger( $this->log_filepath, self::$log_level );
	}

	public function register()
    {
		add_action( 'shutdown', array($this, 'output_captured') );
		add_action( 'current_screen', array($this, 'get_screen') );
		add_filter( 'debug_bar_panels', array($this, 'init_debug_bar_panels') );

		do_action( 'ddbug/ready', $this );
    }

	public function init_debug_bar_panels( $panels )
	{
		array_unshift( $panels, new LogPanel( 'DevDebug Log' ) );
		array_unshift( $panels, new CapturesPanel( 'DevDebug Captures' ) );

		return $panels;
	}

	function get_screen( WP_Screen $screen )
	{
		$this->screen = $screen;
	}

	function print_styles()
	{
		if (! $this->did_styles && ! $this->suppress_output_captured()) {
            printf('<style id="dev-debug-style" type="text/css">%s</style>',
                file_get_contents("{$this->dir}/assets/dist/dev-debug.min.css")
            );
		    $this->did_styles = true;
		}
	}

	public function analyze( $data, $args = array() )
	{
		// maybe record this
		self::log( $data, __METHOD__, DevDebug_Logger::DEBUG );

		$d = array(
			'echo'       => false,
			'backtrace'  => array(),
			'persistent' => false,
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
		// get them all!
		$args['dumps'] = $this->get_dumps( $data );

		$this->captured[] = Capture::fromArray($args);

		// always return data - unless explicitly returning markup
		// allows inline / "chainable" usage
		return $data;
	}

	function get_dumps( $data )
	{
		$d = array();

		$printr = print_r( $data, true );

		$recursion = ( false !== strpos( $printr, '*RECURSION*' ) );

		// var_export()
		$d['varexport'] = array(
			'label' => 'var_export',
			'dump'  => $recursion ? '*RECURSION*' : var_export( $data, true ),
		);
		// var_dump()
		$d['vardump'] = array(
			'label' => 'var_dump',
			'dump'  => $this->get_dump( $data ),
		);
		// print_r()
		$d['printr'] = array(
			'label' => 'print_r',
			'dump'  => $printr,
		);

		return $d;
	}

	/*function exception_handler( $errno, $errstr, $errfile, $errline )
	{
    	throw new ErrorException( $errstr, $errno, 0, $errfile, $errline );
	}*/

	public function capture_count()
	{
		return count( $this->captured );
	}

	public function has_captures()
	{
		return (bool) $this->capture_count();
	}

	/**
	 * Shutdown callback
	 */
	function output_captured()
	{
		if ( $this->suppress_output_captured() )
			return;

		echo '<div id="dev_debug_captures">';

		foreach ( $this->captured as $capture ) {
            $capture->uid = uniqid();
            echo $this->format_output( $capture );
		}

		echo '</div>';

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
						self::log('output suppressed: non-html content-type request', __METHOD__, DevDebug_Logger::DEBUG);
					}

					break;
				}
			}
		}

		if ( empty( $this->captured ) )
		{
			self::log('nothing captured', __METHOD__, DevDebug_Logger::DEBUG);
			$suppress = true;
		}
		elseif ( wp_doing_ajax() )
		{
			self::log('output suppressed: doing ajax', __METHOD__, DevDebug_Logger::DEBUG);
			$suppress = true;
		}
		elseif ( PHP_SAPI == 'cli' )
		{
			self::log('output suppressed: cli', __METHOD__, DevDebug_Logger::DEBUG);
			$suppress = true;
		}
		elseif ( !empty( $this->screen->id ) && ('async-upload' == $this->screen->id) )
		{
			self::log('output suppressed: media upload', __METHOD__, DevDebug_Logger::DEBUG);
			$suppress = true;
		}
		elseif ( apply_filters( 'ddbug/output/footer/suppress', false ) )
		{
			self::log('output suppressed: filter', __METHOD__, DevDebug_Logger::DEBUG);
			$suppress = true;
		}

		return $suppress;
	}

	function format_output( Capture $capture )
	{
		$trace  = $this->get_backtrace_list( $capture->args['backtrace'] );
		$tabs   = $this->render_dump_tabs( $capture );
		$panels = $this->render_dump_panels( $capture );

		$html = <<<HTML
		<div class="ddprint">
			<div class="title">{$capture->args['title']}
				<span class="toggles">
					<label class="toggle-meta" for="meta_{$capture->uid}">
					    <svg role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z"></path></svg>
					</label>
				</span>
			</div>

            <input type="checkbox" id="meta_{$capture->uid}" data-meta-display style="display:none;" />
			<div class="meta" style="display:none;">
				<div class="backtrace">$trace</div>
			</div>

			<div class="output">
				<div class="output-tabs">$tabs</div>
				<div class="panels">$panels</div>
				<div style="clear:both;"></div>
			</div>
		</div>
HTML;

		return $html;
	}

	function render_dump_tabs( Capture $capture )
	{
	    $dumps = $capture->args['dumps'];
		$items = '';
		$keys  = array_keys( $dumps );
		$first = $keys[0];

		foreach ( $dumps as $type => $d ) {
			$classes = $type;

			if ( $first == $type )
				$classes .= ' active';

			$label = sprintf('<label for="%s">%s</label>', "ddcapture_{$capture->uid}_{$type}", $d['label']);
			$items .= "<li class='$classes'>$label</li>";
		}

		return "<ul class='dump-tabs'>$items</ul>";
	}

	function render_dump_panels( Capture $capture )
	{
        $dumps = $capture->args['dumps'];
		$out = '';
		$keys = array_keys( $dumps );
		$first = $keys[0];

		foreach ( $dumps as $type => $d )
		{
			if ('vardump' === $type && extension_loaded('xdebug')) {
			    $dump_html = $d['dump'];
            } else {
			    $dump_html = esc_html( $d['dump'] );
            }
			$checked = checked( $first === $type, true, false );
			$out .= "<input type='radio' id='ddcapture_{$capture->uid}_{$type}' name='ddcapture_{$capture->uid}' style='display: none;' $checked data-dump-panel-display>";
			$out .= "<pre class='dump-panel dump-panel-{$type}' style='display: none;'>$dump_html</pre>\n";
		}

		return $out;
	}

	static function get_dump( $data )
	{
        $max_depth = ini_get('xdebug.var_display_max_depth');
        $max_children = ini_get('xdebug.var_display_max_children');
        $max_data = ini_get('xdebug.var_display_max_data');

        ini_set('xdebug.var_display_max_depth', -1);
        ini_set('xdebug.var_display_max_children', -1);
        ini_set('xdebug.var_display_max_data', -1);

        ob_start();
		var_dump( $data );
		$dump = ob_get_clean();

        ini_set('xdebug.var_display_max_depth', $max_depth);
        ini_set('xdebug.var_display_max_children', $max_children);
        ini_set('xdebug.var_display_max_data', $max_data);

		return $dump;
	}

	/**
	 * Return a backtrace as an html formatted list
	 * @param  [type] $trace [description]
	 * @return [type]        [description]
	 */
	function get_backtrace_list( $trace )
	{
		if ( !is_array( $trace ) && is_scalar( $trace ) )
			return "<div class='trace-error'>$trace</div>";

		$ftrace = $this->prepare_backtrace( $trace );

		$d = array(
			'func' => '',
			'file' => '',
			'line' => '',
			'args' => array(),
		);
		$list = array();

		foreach ( $ftrace as $key => $t )
		{
			$t = array_merge( $d, $t );

			$lineclass = ( $key % 2 ) ? 'even' : 'odd';
			$args_html = !empty( $t['args'] )
				? sprintf(' <span class="args">%s</span> ', implode(', ', $t['args']) )
				: '';

			$filemeta = $t['file'] ? "<span class='file'>{$t['file']}</span><span class='line'>:{$t['line']}</span>" : '';

			$line = <<<HTML
			<div class="trace $lineclass">
				<div class="called">{$t['func']}($args_html)</div>
				<div class='filemeta'>$filemeta</div>
			</div>
HTML;

			$list[] = $line;
		}

		return implode( $list );
	}

	function prepare_backtrace( $trace )
	{
		$f = array();

		foreach ( $trace as $i => $data )
		{
			if ( isset( $data['file'] ) && preg_match('/wp-settings\.php$/', $data['file'] ) )
				break; // don't include this stuff that will be on every function..

			// arguments
			if ( !empty( $data['args'] ) ) {
				$f[ $i ]['args'] = $this->format_args( $data['args'], true );
			}

			// file & line
			foreach ( array('file','line') as $key )
				if ( isset( $data[ $key ] ) )
					$f[ $i ][ $key ] = $data[ $key ];

			// function
			if ( isset( $data['class'] ) )
			{
				$f[ $i ]['func']  = "<span class='class'>{$data['class']}</span>";
				$f[ $i ]['func'] .= "<span class='call-type'>{$data['type']}</span>";
				$f[ $i ]['func'] .= "<span class='method'>{$data['function']}</span>";
			}
			else
				$f[ $i ]['func']  = "<span class='function'>{$data['function']}</span>";
		}

		return $f;
	}

	function format_args( $args, $recurse = false )
	{
		$new = array();
		foreach ( $args as $i => $a )
		{
			if ( is_string( $a ) )
				$n = sprintf("'%s'", esc_html( $a ) );

			elseif ( is_array( $a ) )
			{
				if ( empty( $a ) )
					$n = 'array()';

				// class/object callback
				elseif (
					2 === count( $a )
					&& ( isset( $a[0] ) && isset( $a[1] )										)
					&& ( is_object( $a[0] ) || ( is_string( $a[0] ) && class_exists( $a[0] ) )	)
					&& ( is_string( $a[1] ) && method_exists( $a[0], $a[1] )					)
				)
				{
					$ob = is_object( $a[0] )
						? sprintf('<span class="instance class">%s</span>', get_class( $a[0] ) )
						: sprintf('<span class="class-name">%s</span>', "'{$a[0]}'" );

					$n = sprintf('array( %s, \'%s\' )', $ob, $a[1] );
				}
				else
				{
					if ( $recurse && count( $a ) < 10 )
						$n = 'array('. join( ',', $this->format_args( $a ) ) .')';
					else
						$n = 'array(::'. count( $a ) .'::)';
				}
			}
			elseif ( is_object( $a ) )
				$n = sprintf('<span class="instance class">%s</span>', get_class( $a ) );

			elseif ( is_bool( $a ) )
				$n = sprintf('<span class="boolean %1$s">%1$s</span>', $a ? 'true' : 'false');

			elseif ( is_numeric( $a ) )
				$n = $a;

			elseif ( is_null( $a ) )
				$n = "<span class='null'>NULL</span>";

			else {
				$n = $a;
				self::log( 'Missing type _'. gettype( $a ) . '_ handling!', __METHOD__ );
			}

			$new[ $i ] = "<span class=\"arg\">$n</span>";
		}

		return array_filter( $new );
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

	/**
	 * Returns a sanitized html title attribute value for a given constant
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	function get_const_title( $c )
	{
		$value = self::const_value( $c );
		$data = self::get_dump( $value );
		return esc_attr( strip_tags( $data ) );
	}

	public static function get_realm()
	{
		if ( self::const_value('DOING_AJAX') )
			return 'ajax';

		return is_admin() ? 'admin' : 'front';
	}

	/**
	 * Low-level debugging
	 *
	 * For testing our own doings
	 *
	 * @return [type] [description]
	 */
	public static function log( $msg, $title = null, $level = null )
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

		self::$logger->Log( $log, $level );
	}

}