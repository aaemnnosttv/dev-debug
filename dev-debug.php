<?php

/*
	Plugin Name: DEV Debug
	Description: Development Debug Functions
	Author: Evan Mattson (@aaemnnosttv)
	Version: 1.0
*/

require_once 'classes/DevDebug_Logger.php';
require_once 'classes/DevDebug.php';


#########################
DevDebug::get_instance();
#########################

// API

/**
 * Set Debug Transient
 * @param  [type]  $debug  [description]
 * @param  string  $title  [description]
 * @param  integer $sec    [description]
 * @return [type]          [description]
 */
function sdt( $data, $title = null, $sec = 120 )
{
	$args = array(
		'persistent' => true,
		'backtrace'  => debug_backtrace(false),
		'timeout'    => $sec,
		'title'      => $title,
	);

	return DevDebug::get_instance()->analyze( $data, $args );
}
/**
 * Clear Debug Transient
 * @return [type] [description]
 */
function cdt()
{
	DevDebug::clear_debug_transient();
}
/**
 * Capture data for analysis
 * @param  [type]  $data  [description]
 * @param  boolean $title [description]
 * @param  boolean $echo  [description]
 * @return [type]         [description]
 */
function ddprint( $data, $title = null, $echo = false )
{
	$args = array(
		'echo'      => $echo,
		'backtrace' => debug_backtrace(false),
		'title'     => $title,
	);

	return DevDebug::get_instance()->analyze( $data, $args );
}

function ddlog( $msg )
{
	DevDebug::log( $msg );
	return $msg;
}