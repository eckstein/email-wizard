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

	// Check if we're on the specific template page and the folder_id is set
	if ( is_archive() && isset ( $_GET['folder_id'] ) ) {
		$folder_id = $_GET['folder_id'];
		$user_id = get_current_user_id();
		$user_folders = get_wizard_user_folders( $user_id );

		// Check if the folder exists
		$folder_exists = false;
		foreach ( $user_folders as $folder ) {
			if ( $folder['id'] == $folder_id ) {
				$folder_exists = true;
				break;
			}
		}

		// If the folder doesn't exist, redirect to the main templates page
		if ( ! $folder_exists ) {
			// Get wiz_template archive link
			$allTemplatesArchive = get_post_type_archive_link('wiz_template');

			if ( $allTemplatesArchive ) {
				wp_redirect( $allTemplatesArchive );
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

// Generates the HTML folder structure for display in the breadcrumb
function generate_wizard_folder_breadcrumb( $current_folder_id, $user_folders ) {

	$breadcrumbs = [];
	$folder_lookup = [];
	$templatesArchiveLink = get_post_type_archive_link( 'wiz_template' );

	// Add persistent root folder item
	if ( $current_folder_id === 'root' && !is_singular( 'wiz_template' ) ) {
		$rootHtml = '<span class="breadcrumb-item root">All</span>';
	} else {
		$rootHtml = '<a class="breadcrumb-item root" href="' . esc_url( $templatesArchiveLink ) . '">All</a>';
	}

	// Create a lookup array to easily access folders by their ID
	foreach ( $user_folders as $folder ) {
		$folder_lookup[ $folder['id'] ] = $folder;
	}

	// Start with the current folder and recursively add its ancestors to the breadcrumbs array
	while ( isset ( $folder_lookup[ $current_folder_id ] ) ) {
		$current_folder = $folder_lookup[ $current_folder_id ];
		array_unshift( $breadcrumbs, $current_folder ); // Prepend to keep the order from root to current
		$current_folder_id = $current_folder['parent_id'];
	}

	// Generate the HTML for the breadcrumbs
	$breadcrumbs_html = '<div class="breadcrumb-wrapper">';
	$breadcrumbs_html .= '<div class="breadcrumb-inner">';

	$breadcrumbs_html .= $rootHtml;

	foreach ( $breadcrumbs as $index => $folder ) {
		// Skip the root folder for breadcrumb display
		if ( $folder['id'] === 'root' )
			continue;

		// Separator with Font Awesome icon
		$breadcrumbs_html .= '<i class="fa fa-chevron-right breadcrumb-separator"></i>';

		// Make each folder a link, except the last one which is the current folder
		if ( $index + 1 < count( $breadcrumbs ) || is_singular( 'wiz_template' ) ) {
			$breadcrumbs_html .= '<a class="breadcrumb-item" href="' . esc_url( add_query_arg( 'folder_id', $folder['id'], $templatesArchiveLink ) ) . '"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;' . esc_html( $folder['name'] ) . '</a>';
		} else {
			$breadcrumbs_html .= '<span class="breadcrumb-item current"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;<span class="editable folder-title"  id="' . $folder['id'] . '" data-folder-id="' . $folder['id'] . '">' . esc_html( $folder['name'] ) . '</span><span class="dc-to-edit-message"><i class="fa-solid fa-pencil"></i></span></span>';
		}
	}

	$breadcrumbs_html .= '</div>';
	$breadcrumbs_html .= '</div>';

	return $breadcrumbs_html;
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


