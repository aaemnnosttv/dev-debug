<?php

namespace DevDebug\DebugBar;

use Debug_Bar_Panel;
use DevDebug;

class CapturesPanel extends Debug_Bar_Panel
{
	function prerender()
	{
		$this->set_visible(DevDebug::get_instance()->has_captures());
	}

	function render()
	{
		DevDebug::get_instance()->output_captured();
	}
}
