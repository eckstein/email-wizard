<?php
// Add class to the <body> tag for this page template to identify it in css and js
add_filter( 'body_class', function ($classes) {

	if ( is_singular( 'wiz_template' ) ) {
		$classes[] = 'account-page';
	}
	return $classes;
} );




// When on the /templates page, check for a valid folder_id and redirect to the main templates page if it doesn't exist
add_action( 'template_redirect', 'wizard_custom_redirects' );
function wizard_custom_redirects() {
	global $wp_query, $wp;

	// Handle template archive and search within templates
	if (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'wiz_template') {
		// Redirect search to template archive with search parameter
		$archive_url = get_post_type_archive_link('wiz_template');
		$search_query = get_search_query();
		$redirect_url = add_query_arg([
			's' => $search_query,
			'post_type' => 'wiz_template'
		], $archive_url);
		
		// Preserve other query parameters
		foreach ($_GET as $key => $value) {
			if (!in_array($key, ['s', 'post_type'])) {
				$redirect_url = add_query_arg($key, $value, $redirect_url);
			}
		}

		wp_redirect($redirect_url);
		exit;
	}

	// Check if we're on the specific template page and the folder_id is set
	if (is_archive() && isset($_GET['folder_id'])) {
		$folder_id = $_GET['folder_id'];
		$user_id = get_current_user_id();
		
		// Initialize WizardFolders class
		$folderManager = new WizardFolders($user_id);
		
		// Special case for trash folder
		if ($folder_id === 'trash') {
			$folder_exists = true;
		} else {
			// Use get_folder() method which already handles permissions
			$folder = $folderManager->get_folder($folder_id);
			$folder_exists = ($folder !== null);
		}

		// If the folder doesn't exist, redirect to main templates page
		if (!$folder_exists) {
			$allTemplatesArchive = get_post_type_archive_link('wiz_template');
			if ($allTemplatesArchive) {
				wp_redirect($allTemplatesArchive);
				exit;
			}
		}
	}

	// Handle build-template
	if ( isset ( $wp_query->query_vars['build-wizard-template'] ) ) {

		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		if ( strpos( $current_url, '/build-wizard-template/' ) !== false && ! isset ( $_SERVER['HTTP_REFERER'] ) ) {
			$dieMessage = 'Direct access to the template builder endpoint is not allowed!';
			wp_die( $dieMessage );
			exit;
		}

		//echo '<div style="padding: 30px; text-align: center; font-weight: bold; font-family: Poppins, sans-serif;"><i style="font-family: Font Awesome 5;" class="fas fa-spinner fa-spin"></i>  Loading template...<br/>';
		exit;
	}

}

add_action( 'template_redirect', 'idemailwiz_handle_builder_v2_request', 20 );
function idemailwiz_handle_builder_v2_request() {
	global $wp_query, $wp;


}




// Fiter the main nav based on logged in status and role/caps
function filter_nav_menu_items_by_css_class( $items, $args ) {
	foreach ( $items as $key => $item ) {
		// Hide logged-in only from logged-out users
		if ( in_array( 'logged-in-only', $item->classes ) && ! is_user_logged_in() ) {
			unset( $items[ $key ] );
		}

		// Hide logged-out only from logged-in users
		if ( in_array( 'logged-out-only', $item->classes ) && is_user_logged_in() ) {
			unset( $items[ $key ] );
		}

		// Check for custom wizard_user role
		if ( in_array( 'wizard-user-only', $item->classes ) && ! current_user_can( 'wizard_user' ) ) {
			unset( $items[ $key ] );
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'filter_nav_menu_items_by_css_class', 10, 3 );



// Add this after your existing redirect function
add_filter('template_include', 'wizard_template_search_template');
function wizard_template_search_template($template) {
    if (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'wiz_template') {
        // Use the archive template for template searches
        return get_archive_template();
    }
    return $template;
}


