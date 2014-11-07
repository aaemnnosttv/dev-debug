<?php


class DevDebug_DebugBar_Log extends Debug_Bar_Panel
{
	function init()
	{
		$this->api = DevDebug::get_instance();
		$this->title( 'DevDebug Log' );
		$this->_visible = (bool) filesize( $this->api->log_filepath );
	}

	function render()
	{
		$logpath  = $this->api->log_filepath;
		$modified = filemtime( $logpath ) + get_option('gmt_offset');
		$logtext  = esc_html( file_get_contents( $logpath ) );
		?>
		<div>
			Modified: <code><?php echo date(DATE_RSS, $modified); ?></code>
		</div>
		<pre id="dev_debug_log"><?php echo $logtext ?></pre>
		<?php 
	}
}