<?php


class DevDebug_DebugBar_Captures extends Debug_Bar_Panel
{
	function init()
	{
		ddprint(__METHOD__);
		$this->api = DevDebug::get_instance();
		$this->title( sprintf('DevDebug Captures <span class="count">%s</span>', $this->api->capture_count()) );
	}

	function prerender() {}

	function render()
	{
		$this->api->output_captured();
	}
}