<?php

/**
 * @package DevDebug
 * @author  Evan Mattson @aaemnnosttv
 */

class DevDebug
{

	const slug = 'dev-debug';

	public static $persistent_timeout = 120;

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
	 * [$did_scripts description]
	 * @var [type]
	 */
	private $did_scripts;

	/**
	 * [$hooks description]
	 * @var array
	 */
	private $hooks = array(
		'styles' => array(
			'login_head',
			'wp_print_styles',
			'admin_print_styles'
		),
		'scripts' => array(
			'login_footer',
			'wp_print_footer_scripts',
			'admin_print_footer_scripts'
		)
	);

	
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
		$this->doing_ajax = (bool) self::const_value('DOING_AJAX');

		/**
		 * Set the directory for to write debug log file to
		 * filter 'ddbug/logging/path'
		 */
		$log_dir = apply_filters( 'ddbug/logging/path', WP_CONTENT_DIR );
		self::$logger = new DevDebug_Logger( path_join( $log_dir, '.htdev-debug.log' ), self::$log_level );

		// init
		add_action( 'init',	array(&$this, 'init') );
		// output!
		add_action( 'shutdown', array(&$this, 'output_captured') );
	}

	function init()
	{
		if ( $this->show_in_admin_bar() && is_admin_bar_showing() )
			add_action( 'admin_bar_menu',	array(&$this, 'dev_admin_menu') );

		add_action( 'admin_notices',	array(&$this, 'print_persistent_capture' ) );
		add_action( 'current_screen',	array(&$this, 'get_screen') );

		foreach ( $this->hooks['styles'] as $hook )
			add_action( $hook,	array(&$this, 'print_styles') );

		foreach ( $this->hooks['scripts'] as $hook )
			add_action( $hook,	array(&$this, 'print_scripts') );
	}

	/**
	 * Check if wp admin bar node should be created or not
	 *
	 * define('DEVDEBUG_NO_ADMIN_BAR', 1); // to hide
	 * 
	 * @return [boolean]
	 */
	public function show_in_admin_bar()
	{
		return !( self::const_value( 'DEVDEBUG_NO_ADMIN_BAR' ) );
	}

	function get_screen()
	{
		$this->screen = get_current_screen();
	}

	function print_styles()
	{
		$css = file_get_contents( "{$this->dir}/assets/dev-debug.css" );
		echo "<!-- DevDebug Styles -->\n
		<style type='text/css'>$css</style>\n";
		$this->did_styles = true;
	}

	function print_scripts()
	{
		$scripts = file_get_contents( "{$this->dir}/assets/dev-debug.min.js" );
		echo "<!-- DevDebug Scripts -->\n
		<script type='text/javascript'>$scripts</script>\n";
		$this->did_scripts = true;
	}

	public function analyze( $data, $args = array() )
	{
		// maybe record this
		self::log( $data, DevDebug_Logger::DEBUG );

		$d = array(
			'echo'       => false,
			'backtrace'  => array(),
			'persistent' => false,
			'timeout'    => self::$persistent_timeout,
		);
		$args = wp_parse_args( $args, $d );
		$args['title'] = isset( $args['title'] ) ? $args['title'] : gettype( $data );


		if ( $args['persistent'] )
		{
			//self::log( $args );
			self::set_debug_transient( $data, $args );
		}

		extract( $args );

		$args['data'] = $data;
		// get them all!
		$args['dumps'] = $this->get_dumps( $data );

		$formatted = $this->format_output( $args );

		if ( $echo && !$this->doing_ajax )
			echo $formatted;

		elseif ( false === $echo )
			$this->captured[] = $args; // unformatted

		else
			return $formatted; // 0, null, '', ...

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

	/**
	 * Shutdown callback
	 */
	function output_captured()
	{
		if ( $this->suppress_output_captured() )
			return;

		echo '<div id="dev_debug_captures">';

		foreach ( $this->captured as $key => $args )
		{
			//if ( $key == (count( $this->captured )-1) )
			//	$args['classes'] = 'last';

			echo $this->format_output( $args );
		}

		echo '</div>';


		// if something catastrophic happened,
		// make sure we're still lookin' good
		if ( !$this->did_styles )
		{
			$this->print_styles();
		}

		if ( !$this->did_scripts )
		{
			wp_print_scripts( 'jquery' );
			$this->print_scripts();
		}
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

		if ( empty( $this->captured ) )
		{
			self::log('nothing captured', DevDebug_Logger::DEBUG);
			$suppress = true;
		}
		elseif ( $this->doing_ajax )
		{
			self::log('output suppressed: doing ajax', DevDebug_Logger::DEBUG);
			$suppress = true;
		}
		elseif ( !empty( $this->screen->id ) && ('async-upload' == $this->screen->id) )
		{
			self::log('output suppressed: media upload', DevDebug_Logger::DEBUG);
			$suppress = true;
		}
		elseif ( apply_filters( 'ddbug/output/footer/suppress', false ) )
		{
			self::log('output suppressed: filter', DevDebug_Logger::DEBUG);
			$suppress = true;
		}

		return $suppress;
	}

	function format_output( $args = array() )
	{
		$d = array(
			'classes' => '',
		);

		extract( wp_parse_args( $args, $d ) );

		if ( $classes && is_string( $classes ) )
			$classes = explode( ' ', $classes );

		$classes = implode(' ', (array) $classes);

		$trace  = $this->get_backtrace_list( $backtrace );
		$tabs   = $this->render_dump_tabs( $dumps );
		$panels = $this->render_dump_panels( $dumps );

		$html = <<<HTML
		<div class="ddprint $classes">
			<div class="title">$title
				<span class="toggles">
					<a href="#" class="toggle-meta"><i class="ddico-ellipsis"></i></a>
				</span>
			</div>

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

	function render_dump_tabs( $dumps )
	{
		$items = '';
		$keys  = array_keys( $dumps );
		$first = $keys[0];

		foreach ( $dumps as $type => $d )
		{
			$classes = $type;

			if ( $first == $type )
				$classes .= ' active';

			$items .= "<li class='$classes'><a href='#' data-dump-type='$type'>{$d['label']}</a></li>";
		}

		return "<ul class='dump-tabs'>$items</ul>";
	}

	function render_dump_panels( $dumps )
	{
		$out = '';
		$keys = array_keys( $dumps );
		$first = $keys[0];

		foreach ( $dumps as $type => $d )
		{
			$class = ( $first == $type ) ? 'active' : '';
			$dump_html = esc_html( $d['dump'] );
			$out .= "<pre class='dump-panel $class' dump-type='$type'>$dump_html</pre>\n";
		}

		return $out;
	}

	static function get_dump( $data )
	{
		ob_start();
		var_dump( $data );
		return ob_get_clean();
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

			$filemeta = $t['file'] ? "<span class='file'>{$t['file']}</span> <span class='line'>{$t['line']}</span>" : '';

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
				$f[ $i ]['func'] = "
					<span class='class'>{$data['class']}</span>
					<span class='call-type'>{$data['type']}</span>
					<span class='method'>{$data['function']}</span>
					";
			}
			else
				$f[ $i ]['func'] = "<span class='function'>{$data['function']}</span>";
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
					if ( $recurse )
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
				self::log( gettype( $a ), __METHOD__.' - Missing type handling!' );
			}

			$new[ $i ] = "<span class=\"arg\">$n</span>";
		}

		return array_filter( $new );
	}

	/**
	 * Sets the debug data
	 * Useful for capturing data when ddprint cannot
	 * (eg: during ajax callback, pre-redirect, or pre-fatal error, etc)
	 * @param [type]  $value  	debug data
	 * @param integer $sec    	transient timeout
	 */
	static function set_debug_transient( $data, $args = array() )
	{
		extract( $args );

		try {
			maybe_serialize( $backtrace ); // this blows up when trying to serialize a Closure
		} catch ( Exception $e ) {
		    self::log( 'Caught exception: ',  $e->getMessage() );
		    $backtrace = $e->getMessage();
		}

		$capture = array(
			'data'      => $data,
			'title'     => $title,
			'backtrace' => $backtrace, 
			'time'      => current_time('timestamp')
		);

		set_transient( 'dev_debug', $capture, $timeout );
	}

	public static function clear_debug_transient()
	{
		if ( self::is_debug_set() )
			delete_transient( 'dev_debug' );
	}


	/**
	 * Echos contents of debug transient into admin header
	 */
	function print_persistent_capture()
	{
		/*if ( !is_user_logged_in() || !current_user_can('administrator') )
			return;*/

		// clear transient with url
		if ( isset( $_GET['cdt'] ) && $_GET['cdt'] )
			self::clear_debug_transient();

		$set = self::is_debug_set();
		$sep = '<span class="sep">|</span>';

		?>
		<div id="dev_debug_persistent" class="dev_debug <?php echo $set ? 'set' : ''; ?>">
			<?php

			echo '<span class="title">DEBUG</span> ';

			if ( $set )
			{
				extract( get_transient('dev_debug') );

				$output = $this->analyze( $data, array( 'echo' => 0, 'backtrace' => $backtrace ) ); // return

				$meta = date( '@ g:i:s a', $time );

				$title = $title ? "$meta $sep $title" : $meta;
			}
			else
			{
				$title  = sprintf('<em class="disabled"> %s </em>',
					!empty( $_GET['cdt'] ) ? 'cleared' : 'not set'
				);
				$output = '';
			}

			echo "<span class='meta'>$title</span> <span class='output'>$output</span>";
			?>
		</div>
		<?php
	}

	public static function is_debug_set()
	{
		return is_array( get_transient('dev_debug') );
	}

	function dev_admin_menu( $wpab )
	{
		// top level menu
		$wpab->add_menu( array(
			'parent' => 'top-secondary',
			'id'     => self::slug,
			'title'  => __CLASS__,
		) );

		// clear debug transient
		$wpab->add_menu( array(
			'parent' => self::slug,
			'id'     => 'clear-debug',
			'title'  => 'CLEAR DEBUG',
			'href'   => add_query_arg( array('cdt' => 1) )
		) );

		$wpab->add_group( array(
			'parent' => self::slug,
			'id'     => "{self::slug}_constants",
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		$constants = array(
			'WP_DEBUG'               => true, // WP Debug / PHP error reporting
			'WP_DEBUG_LOG'           => true, // PHP error logging 
			'WP_HTTP_BLOCK_EXTERNAL' => true, // disable WP HTTP class functions (wp_remote_get/post()...)
			'SCRIPT_DEBUG'           => true, // uncompressed, non-concatenated scripts & styles (WP)
			'RELOCATE'               => true, // for site migrations
		);

		/**
		 * filter 'ddbug/admin_bar/constants'
		 * @var array 	[CONSTANT => (bool) active]
		 */
		$constants = apply_filters( 'ddbug/admin_bar/constants', $constants );

		foreach ( $constants as $c => $active )
		{
			if ( ! $active )
				continue;

			$defined = defined( $c );
			$value   = self::const_value( $c );
			$classes = array( $defined ? 'defined' : 'undefined' );

			if ( $defined )
				$classes[] = $value ? 'enabled' : 'disabled';

			$wpab->add_menu( array(
				'parent' => "{self::slug}_constants",
				'id'     => strtolower( $c ),
				'title'  => "<span>$c</span>",
				'meta'   => array(
					'class' => implode(' ', $classes),
					'title' => $defined ? $this->get_const_title( $c ) : 'UNDEFINED',
				)
			) );
		}
	} // dev_admin_menu

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


	/**
	 * Low-level debugging
	 *
	 * For testing our own doings
	 *
	 * @return [type] [description]
	 */
	public static function log( $msg, $level = null )
	{
		if ( is_null( $level ) )
			$level = DevDebug::$log_level;

		$realm = is_admin() ? 'admin' : 'front';
		
		if ( is_scalar( $msg ) )
			self::$logger->Log( "($realm) - $msg", $level );
		else
			self::$logger->Log( $msg, $level );
	}

}