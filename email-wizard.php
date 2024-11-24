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

// Start session if not already started
function wizard_start_session() {
	if (!session_id() && !headers_sent()) {
		session_start();
	}
}
add_action('init', 'wizard_start_session', 1);

// Globals
$pluginVersion = '1.0.0';
define( 'EMAILWIZARD_VERSION', $pluginVersion );

// Wp-Admin dependencies
foreach ( glob( plugin_dir_path( __FILE__ ) . 'admin/*.php' ) as $file ) {
	include_once $file;
}

// Non-Wp-Admin dependencies
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(plugin_dir_path(__FILE__) . 'includes'));
foreach ($iterator as $file) {
	if ($file->isFile() && $file->getExtension() === 'php') {
		include_once $file->getPathname();
	}
}



// Plugin activation hook
function email_wizard_activate() {
	// Custom post types and taxonomies
	init_wizard_post_types();
	init_wizard_taxonomies();

	// Custom databases
	create_wizard_tables();

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
