<?php

/*
	Plugin Name: Dev Debug
	Description: Development Debug Functions
	Author: Evan Mattson (@aaemnnosttv)
	Version: 1.0
*/

use DevDebug\DevDebug;

define('DEVDEBUG_FILE', __FILE__);
define('DEVDEBUG_DIR', __DIR__);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

devdebug( new DevDebug() )->register();
