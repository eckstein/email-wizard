<?php
add_filter( 'template_include', function ($template) {
	// Account page
	if ( is_page( get_option( 'wizard_account_page_id' ) ) ) {
		$new_template = plugin_dir_path( dirname( __FILE__ ) ) . 'public/templates/account/page-account.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}

	// Single templates
    if ( is_singular( 'wiz_template' ) ) {
		$new_template = plugin_dir_path( dirname( __FILE__ ) ) . 'public/templates/single-template/single-wiz_template.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}

    // Templates archive
	if ( is_post_type_archive( 'wiz_template' ) ) {
		$new_template = plugin_dir_path( dirname( __FILE__ ) ) . 'public/templates/template-archive/archive-wiz_template.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}

	return $template;
} );