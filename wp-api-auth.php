<?php

/**
 *    Plugin Name:       WP API AUTH
 *    Plugin URI:        https://www.nonmadden.com
 *    Description:       WP API AUTH enables a UI to toggle endpoints in the REST API.
 *    Version:           1.0.0
 *    Author:            Non Madden
 *    Plugin URI:       https://github.com/nonmadden/wp-api-auth
 *    Author URI:        https://www.nonmadden.com
 *    License:           GPL-3.0+
 *    License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 *    Text Domain:       wp-api-auth
 *    Domain Path:       /languages
**/

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

if (! defined('ABSPATH')) {
    die("You can't do anything by accessing this file directly.");
}

require plugin_dir_path(__FILE__) . 'includes/class-wp-api-auth.php';

function run_wp_api_auth()
{
    $plugin = new wp_api_auth();
    $plugin->run();
}

run_wp_api_auth();
