<?php


class DevDebug_DebugBar_Captures extends Debug_Bar_Panel
{
	function init()
	{
		$this->api = DevDebug::get_instance();
	}

	function prerender()
	{
		$this->title( sprintf('DevDebug Captures <span class="count">%s</span>', $this->api->capture_count()) );
		$this->_visible = $this->api->has_captures();
	}

	function render()
	{
		$this->api->output_captured();
	}
}