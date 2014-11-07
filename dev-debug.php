<?php

/*
	Plugin Name: Dev Debug
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

if ( !function_exists('sdt') ) :
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
endif;

if ( !function_exists('cdt') ) :
/**
 * Clear Debug Transient
 * @return [type] [description]
 */
function cdt()
{
	DevDebug::clear_debug_transient();
}
endif;

if ( !function_exists('ddprint') ) :
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
endif;

if ( !function_exists('ddlog') ) :
/**
 * [ddlog description]
 * @param  [type] $msg   [description]
 * @param  [type] $title [description]
 * @return [type]        [description]
 */
function ddlog( $msg, $title = null )
{
	DevDebug::log( $msg, $title, DevDebug_Logger::INFO );

	return $msg;
}
endif;

if ( !function_exists('ddhook') ) :
/**
 * Inspect the registered callbacks of a hook
 * @param  [type]  $hook [description]
 * @param  integer $args [description]
 * @return [type]        [description]
 */
function ddhook( $hook, $args = 1 )
{
	add_action( $hook, '_ddhook', 0, $args );
}
endif;

if ( !function_exists('_ddhook') ) :
/**
 * [_ddhook description]
 * @param  [type] $return [description]
 * @return [type]         [description]
 */
function _ddhook( $return )
{
	$DD    = DevDebug::get_instance();
	$args  = func_get_args();
	$hook  = current_filter();
	$trace = debug_backtrace(false);

	$DD->analyze( $args, array(
		'backtrace' => $trace,
		'title'     => "$hook args",
	) );
	$DD->analyze( $GLOBALS['wp_filter'][ $hook ], array(
		'backtrace' => $trace,
		'title'     => $hook,
	) );

	return $return;
}
endif;
