Dev Debug
=========

A WordPress plugin for developing plugins.

Dev Debug provides some handy functions for analyzing your code and giving some helpful feedback.

In order to be available for use in early plugin execution, Dev Debug needs to be included first. It's for this reason that Dev Debug was designed as a [MU plugin](http://codex.wordpress.org/Must_Use_Plugins), but it may also be installed as a normal plugin.

> Disclaimer: This plugin is a handy tool. It is not intended as a replacement for a real debugging solution like [Xdebug](http://xdebug.org/).  If you don't know what xdebug can do, drop what you're doing and check that out _immediately_. 
> You can thank me later :smile:

## Basic Usage

### Non-persistent Data Capture

Dev Debug's primary feature is essentially a super var_dump.  Want to dump the contents of a variable/expression?

Simply use `ddprint( $somevariable );` and you will see a nice dump at the bottom of the page.

_Example:_
```php
// some test in your plugin or theme's functions.php
add_action( 'wp_head', function() {
	ddprint( get_queried_object() );
} );

```

<img src="http://f.cl.ly/items/0I0U342C0S213K113P3H/Image%202014-03-24%20at%2012.10.24%20PM.png" alt="">

Want to label your dump? `ddprint( $data, $label )`  (label defaults to the datatype of `$data` ). Useful when capturing many items at a time, eg: within the loop.

Each dump shows up as an individual panel at the bottom of the loaded page, regardless of where you are in WP.
The default dump representation is `var_export` but you can toggle between `var_dump` and `print_r` (I find they each have their uses).

Each dump also has a full comprehensive sexy lookin backtrace with which you can reveal with a toggle button at the top right corner of the panel.

<img src="http://cl.ly/image/0A2F3e3O0X3r/Image%202014-03-24%20at%2012.12.39%20PM.png" alt="">

> Captured output is suppressed during ajax requests and media uploads to prevent the output from interferring with the expected returned data.

### Persistent Capture

Dev Debug also provides two functions for capturing data in circumstances where `ddprint()` would not work, such during as an ajax request.

#### sdt()
```php

sdt( $data, $label = 'optional' );
```
This function saves the captured data as a transient. It's captured output will appear with other captures as long as the data is still set.

The `sdt()` capture has a short lifespan of only 2 minutes by default.

#### ddlog()
```php

ddlog( $data, $label = 'optional' );
```
This function writes the data to a log file.
By default, the file is located in the `wp-content/.htdev-debug.log`.  The destination directory can be changed with the `ddbug/logging/path` filter if desired.

> Most standard Apache and Nginx configurations include rules to block external access to files that begin with `.ht` such as `.htaccess` and `.htpasswd`.  The `.htdev-debug` file could possibly contain very sensitive information so it is not recommended to be used on production environments, however this filename aims to protect unauthorized access for those that choose to live on the wild side.

## Hook Inspection

Ever want to know what the heck is going on during a particular action or filter?

```php
ddhook('the_content')
```
[![thumb](http://f.cl.ly/items/320o0T3r3w2M0Z2J2X0c/Image%202015-05-03%20at%209.53.26%20PM.png)](http://cl.ly/image/2E192D2B0Y0A)

The `ddhook` function will produce a non-persistent capture of all of the callbacks attached to the given hook.
It can also be configured to listen for a given number of arguments that are passed, and provide an additional capture of the arguments passed to the callback.

![thumb](https://s3.amazonaws.com/f.cl.ly/items/152K1M0A1S3Y2i0G2s2R/Image%202015-05-03%20at%209.55.17%20PM.png)

## Menu Item

Dev Debug also adds an admin menu item for clearing the persistent debug capture if set, and a very accessible list of constants.

<img src="http://cl.ly/image/2a070b1A1q1n/Image%202014-03-24%20at%2012.25.01%20PM.png" alt="">  

Constants with truthy values are green, falsy are gray, and undefined are faded/italic for quick reference. The constant's values can also be seen on hover.

## Debug Bar

Dev Debug integrates with the [Debug Bar](https://wordpress.org/plugins/debug-bar/) plugin out of the box, by adding a panels to its overlay for showing captured dumps, as well as quick in-app access to viewing the Dev Debug log.

![thumb](http://f.cl.ly/items/1W1v2i1W210y3q2N272U/Image%202015-05-03%20at%209.59.22%20PM.png)

## Installation

Dev Debug can be installed in a few different ways.

### Via Composer
Use
`composer require aaemnnosttv/dev-debug`
to add the latest release of the package to your project and install it as an MU plugin.

### Via Git
Clone the project into either:

* `{path-to-wp-content}/mu-plugins`
* `{path-to-wp-content}/plugins`


`git clone git@github.com:aaemnnosttv/dev-debug.git`

### Manual Installation
* [Download the desired release](https://github.com/aaemnnosttv/dev-debug/releases).
* Extract directory into one of the two directories listed above.
* Optionally rename the directory to `dev-debug` if it is not already.

---

#### Note on installing as a MU plugin
WordPress only loads plugins within the root directory of the `mu-plugins` directory. It does not recurse into sub-directories as it does with `plugins`.
This means that you will need to load the main plugin file yourself.

##### Create a simple MU loader for Dev Debug:

Create a file called `load-dev-debug.php` within the root of the `mu-plugins` directory.
Add this code:

```php

if ( file_exists( __DIR__ . '/dev-debug/dev-debug.php' ) )
    require_once ( __DIR__ . '/dev-debug/dev-debug.php' );

```

The loader file can be named anything, but WordPress will load it in alphabetical order.  So if you want Dev Debug functions to be available within another MU plugin, you'll just need to ensure that it loads first.
