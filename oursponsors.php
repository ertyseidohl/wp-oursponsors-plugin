<?php
/**
 * @package OurSponsors
 */
/*
Plugin Name: OurSponsors
Plugin URI: http://purecode.com/products/our_sponsors
Description: Manage sponsors
Version: 0.1
Author: Purecode Computing, LLC
Author URI: http://purecode.com
License: CC-BY-NC-SA
Text Domain: our_sponsors
*/

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'OURSPONSORS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( OURSPONSORS__PLUGIN_DIR . 'class.oursponsors.php' );

register_activation_hook( __FILE__, array( 'OurSponsors', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'OurSponsors', 'plugin_deactivation' ) );

add_action( 'init', array( 'OurSponsors', 'init' ) );
