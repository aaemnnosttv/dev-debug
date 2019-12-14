<?php

namespace DevDebug\DebugBar;

use Debug_Bar_Panel;
use DevDebug;

class LogPanel extends Debug_Bar_Panel
{
    function init()
	{
	    $this->set_visible(file_exists( DevDebug::get_instance()->log_filepath ));
	}

	function render()
	{
		$logpath  = DevDebug::get_instance()->log_filepath;
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
