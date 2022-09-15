<?php
/*
Plugin Name: BTN Podcasts
Plugin URI: https://businesstechninjas.com/
Description: Podcasts w/ Memberium Access Functionality
Version: 1.0.0
Author: Business Tech Ninjas
Author URI: https://businesstechninjas.com/
License: Copyright (c) Business Tech Ninjas
Text Domain: btn-podcasts
*/

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

define('BTN_PODCASTS_VERSION', '1.0.8');
define('BTN_PODCASTS_PLUGIN', __FILE__);
define('BTN_PODCASTS_DIR', __DIR__ . '/');
define('BTN_PODCASTS_CLASS_DIR', BTN_PODCASTS_DIR . 'classes/');
define('BTN_PODCASTS_TMPL_DIR', BTN_PODCASTS_DIR . 'templates/');
$btn_podcasts_url = plugins_url('', __FILE__);
define('BTN_PODCASTS_URL', $btn_podcasts_url . '/');
define('BTN_PODCASTS_ASSESTS_URL', BTN_PODCASTS_URL . 'assets/');

// Include Autoloader
include_once BTN_PODCASTS_CLASS_DIR . 'autoloader.php';

// Init Plugin
add_action('plugins_loaded',function(){
	btn_podcasts()->init();
}, 1 );

// Gets the instance of the `btn_podcasts` class
function btn_podcasts(){
    return btn_podcasts::get_instance();
}
