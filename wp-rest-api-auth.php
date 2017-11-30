<?php
/**
 *    Plugin Name:       WP REST API AUTH
 *    Plugin URI:        https://www.nonmadden.com
 *    Description:       WP REST API AUTH enables a UI to toggle endpoints in the REST API.
 *    Version:           1.0.0
 *    Author:            Non Madden
 *    Author URI:        https://www.nonmadden.com
 *    License:           GPL-3.0+
 *    License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 *    Text Domain:       wp-rest-api-auth
 *    Domain Path:       /languages
**/


// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

require plugin_dir_path(__FILE__) . 'includes/class-wp-rest-api-auth.php';

function run_wp_rest_api_auth()
{
    $plugin = new wp_rest_api_auth();
    $plugin->run();
}

run_wp_rest_api_auth();
