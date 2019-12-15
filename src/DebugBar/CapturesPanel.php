<?php

namespace DevDebug\DebugBar;

use Debug_Bar_Panel;
use DevDebug;

class CapturesPanel extends Debug_Bar_Panel
{
	function prerender()
	{
		$this->set_visible(devdebug()->has_captures());
	}

	function render()
	{
		devdebug()->output_captured();
	}
}
