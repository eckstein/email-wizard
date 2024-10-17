<?php
/*
Plugin Name: Email Wizard
Plugin URI: http://yourwebsite.com/emailwizard
Description: Build and manage expert-level email templates that work in every inbox.
Version: 1.0
Author: Your Name
Author URI: http://yourwebsite.com
License: GPLv2 or later
Text Domain: emailwizard
*/

// Security measure to prevent direct access to the PHP files.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Globals
$pluginVersion = '1.0.0';
define( 'EMAILWIZARD_VERSION', $pluginVersion );

// Wp-Admin dependencies
foreach ( glob( plugin_dir_path( __FILE__ ) . 'admin/*.php' ) as $file ) {
	include_once $file;
}

// Non-Wp-Admin dependencies
foreach ( glob( plugin_dir_path( __FILE__ ) . 'includes/*.php' ) as $file ) {
	include_once $file;
}

// Enqueue scripts and styles
include_once plugin_dir_path( __FILE__ ) . 'enqueue.php';


// Plugin activation hook
function email_wizard_activate() {
	// Custom post types and taxonomies
	init_wizard_post_types();
	init_wizard_taxonomies();
	init_wizard_wp_templates();

	// Custom databases
	create_wiz_templates_table();

	// Custom ajax endpoint
	wiz_add_custom_endpoint();
	flush_rewrite_rules();
	
}

register_activation_hook( __FILE__, 'email_wizard_activate' );

// Plugin deactivation hook
function email_wizard_deactivate() {
	// Deactivation code here
	// Example: Delete options, remove transients or cron jobs, etc
}

register_deactivation_hook( __FILE__, 'email_wizard_deactivate' );
