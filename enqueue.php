<?php
function email_wizard_enqueue_assets() {
	// Enqueue styles
	wp_enqueue_style('swal2-style', plugins_url('node_modules/sweetalert2/dist/sweetalert2.min.css', __FILE__), array(), EMAILWIZARD_VERSION);
	wp_enqueue_style('select2-style', plugins_url('node_modules/select2/dist/css/select2.min.css', __FILE__), array(), EMAILWIZARD_VERSION);
	wp_enqueue_style('emailwizard-style', plugins_url('assets/css/style.css', __FILE__), array(), EMAILWIZARD_VERSION);

	// Enqueue a single bundled script (made with concat)
	wp_enqueue_script('emailwizard-bundle', plugins_url('assets/js/bundle.js', __FILE__), array('jquery'), EMAILWIZARD_VERSION, true);

	// Localize the script
	email_wizard_localize_script('emailwizard-bundle');
}
add_action('wp_enqueue_scripts', 'email_wizard_enqueue_assets');

function email_wizard_localize_script($scriptName) {
	$nonce = wp_create_nonce('wizard_security');
	$localized_data = array(
	'nonce' => $nonce,
	'ajaxurl' => home_url('/wiz-ajax/'),
	'currentPost' => get_post(get_the_ID()),
	'stylesheet' => plugins_url('', __FILE__),
	'plugin_url' => plugin_dir_url(__FILE__),
	'site_url' => get_bloginfo('url'),
	'current_user' => wp_get_current_user(),
	);

	wp_localize_script($scriptName, 'wizard', $localized_data);
}