<?php
add_filter( 'template_include', function ($template) {
	// Single templates
    if ( is_singular( 'wiz_template' ) ) {
		$new_template = plugin_dir_path( dirname( __FILE__ ) ) . 'public/templates/single-wiz_template.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}

    // Templates archive
	if ( is_post_type_archive( 'wiz_template' ) ) {
		$new_template = plugin_dir_path( dirname( __FILE__ ) ) . 'public/templates/archive-wiz_template.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}

    // Individual pages
	// Account page
	if ( is_page( get_option( 'wizard_account_page_id' ) ) ) {
		$new_template = plugin_dir_path( dirname( __FILE__ ) ) . 'public/templates/account-page.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}

	return $template;
} );