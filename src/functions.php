<?php

use DevDebug\Logger;

/**
 * @param DevDebug|null $debug
 *
 * @return DevDebug
 */
function devdebug( DevDebug $debug = null )
{
    static $instance;

    if ( $debug ) {
        $instance = $debug;
    }

    return $instance;
}

/**
 * Capture data for analysis
 * @param  [type]  $data  [description]
 * @param  boolean $title [description]
 * @return [type]         [description]
 */
function ddprint( $data, $title = null )
{
    $args = array(
        'backtrace' => debug_backtrace(false),
        'title'     => $title,
    );

    return devdebug()->analyze( $data, $args );
}

/**
 * [ddlog description]
 * @param  [type] $msg   [description]
 * @param  [type] $title [description]
 * @return [type]        [description]
 */
function ddlog( $msg, $title = null )
{
    devdebug()->log( $msg, $title, Logger::INFO );

    return $msg;
}

/**
 * Inspect the registered callbacks of a hook
 * @param  [type]  $hook [description]
 * @param  integer $args [description]
 */
function ddhook( $hook, $args = 1 )
{
    add_action( $hook, '_ddhook', 0, $args );
}

/**
 * [_ddhook description]
 * @param  [type] $return [description]
 * @return [type]         [description]
 */
function _ddhook( $return )
{
    $args  = func_get_args();
    $hook  = current_filter();
    $trace = debug_backtrace(false);

    devdebug()->analyze( $args, array(
        'backtrace' => $trace,
        'title'     => "$hook args",
    ) );
    devdebug()->analyze( $GLOBALS['wp_filter'][ $hook ], array(
        'backtrace' => $trace,
        'title'     => $hook,
    ) );

    return $return;
}
