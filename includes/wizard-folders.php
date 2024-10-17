<?php
function init_wizard_user_folders( $user_id ) {
	$user_folders = json_decode( get_user_meta( $user_id, 'user_folders', true ), true ) ?? [];

	// Ensure there is always a 'root' folder
	$has_root = false;
	foreach ( $user_folders as $folder ) {
		if ( $folder['id'] === 'root' ) {
			$has_root = true;
			break;
		}
	}

	if ( ! $has_root ) {
		array_unshift( $user_folders, [ 
			'id' => 'root',
			'name' => 'Root',
			'parent_id' => null,
		] );
	}

	// De-dupe folders by 'id' without losing folder structure
	$seen_ids = [];
	$user_folders = array_filter( $user_folders, function ($folder) use (&$seen_ids) {
		if ( in_array( $folder['id'], $seen_ids ) ) {
			return false; // Skip folders with duplicate 'id'
		} else {
			$seen_ids[] = $folder['id'];
			return true; // Keep unique folders
		}
	} );

	update_user_meta( $user_id, 'user_folders', json_encode( $user_folders ) );
}

add_action( 'user_register', 'init_wizard_user_folders' );



// Add folder on ajax request
add_action( 'wp_ajax_add_wizard_user_folder', 'ajax_add_wizard_user_folder' );
function ajax_add_wizard_user_folder() {
	// Verify the nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wizard_security' ) ) {
		wp_die( 'Invalid nonce' );
	}

	// Check if the parent ID is provided
	if ( ! isset ( $_POST['parent_id'] ) ) {
		wp_send_json_error( 'Folder creation failed: parent folder not found' );
	}

	// Check if the folder name is provided
	if ( ! isset ( $_POST['folder_name'] ) ) {
		wp_send_json_error( 'Folder creation failed: no folder name provided' );
	}

	// Sanitize the input data
	$parent_id = sanitize_text_field( $_POST['parent_id'] );
	$folder_name = sanitize_text_field( $_POST['folder_name'] );

	// Get the current user ID
	$user_id = get_current_user_id();

	// Add the new folder
	$new_folder_id = add_wizard_user_folder( $user_id, $folder_name, $parent_id );

	// Send a success response with the new folder ID
	wp_send_json_success( $new_folder_id );
}

function add_wizard_user_folder( $user_id, $folder_name, $parent_id = 'root' ) {
	$user_folders = json_decode( get_user_meta( $user_id, 'user_folders', true ), true );
	$new_folder_id = uniqid( 'wizF_' );

	$user_folders[] = [ 
		'id' => $new_folder_id,
		'name' => $folder_name,
		'parent_id' => $parent_id,
	];

	update_user_meta( $user_id, 'user_folders', json_encode( $user_folders ) );

	return $new_folder_id;
}

add_action( 'wp_ajax_get_wizard_user_folders', 'ajax_get_wizard_user_folders' );
function ajax_get_wizard_user_folders() {

	// Verify the nonce
	if ( ! wp_verify_nonce( $_GET['nonce'], 'wizard_security' ) ) {
		wp_die( 'Invalid nonce' );
	}

	// Get the current user ID
	$user_id = get_current_user_id();

	// Check for exclusions
	$exclude = $_GET['exclude'] ?? [];

	// Get user folders
	$user_folders = get_wizard_user_folders( $user_id, $exclude );

	// Transform the folder data into the format expected by jsTree
	$folder_data = array();
	foreach ( $user_folders as $folder ) {
		$folder_data[] = array(
			'id' => $folder['id'],
			'parent' => $folder['parent_id'] ? $folder['parent_id'] : '#',
			'text' => $folder['name'],
		);
	}

	error_log( 'Folder data: ' . print_r( $folder_data, true ) );

	wp_send_json_success( $folder_data );
}

function get_wizard_user_folders( $user_id, $exclude = [] ) {
	$user_folders = json_decode( get_user_meta( $user_id, 'user_folders', true ), true );
	if ( empty ( $user_folders ) ) {
		// Initialize if not already done
		init_wizard_user_folders( $user_id );
		$user_folders = json_decode( get_user_meta( $user_id, 'user_folders', true ), true );
	}
	// check for excluded ids in array and remove them
	if ( ! empty ( $exclude ) ) {
		$user_folders = array_filter( $user_folders, function ($folder) use ($exclude) {
			return ! in_array( $folder['id'], $exclude ) && ! in_array( $folder['parent_id'], $exclude );
		} );
	}


	return $user_folders;
}

add_action( 'wp_ajax_get_templates_in_wizard_user_folder', 'ajax_get_templates_in_wizard_user_folder' );
function ajax_get_templates_in_wizard_user_folder() {

	// Verify the nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wizard_security' ) ) {
		wp_die( 'Invalid nonce' );
	}
	// Check if the folder name is provided
	if ( ! isset ( $_POST['folder_id'] ) ) {
		wp_send_json_error( 'Folder id was not provided' );
	}
	// Get folder ID from request
	$folder_id = sanitize_text_field( $_POST['folder_id'] );

	// Get templates in folder
	$templates = get_templates_in_wizard_user_folder( $folder_id );
	wp_send_json_success( $templates );
}

function get_templates_in_wizard_user_folder( $folder_id ) {
	$args = [ 
		'post_type' => 'wiz_template',
		'meta_key' => 'user_folder',
		'meta_value' => $folder_id,
		'posts_per_page' => -1,
	];

	$query = new WP_Query( $args );
	return $query->posts;
}

add_action( 'wp_ajax_ajax_update_template_wizard_user_folder', 'ajax_update_template_wizard_user_folder' );
function ajax_update_template_wizard_user_folder() {

	// Check nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wizard_security' ) ) {
		wp_die( 'Invalid nonce' );
	}

	// check for template ID
	if ( ! isset ( $_POST['template_id'] ) ) {
		wp_send_json_error( 'Template ID not provided' );
	}
	// Check folder ID
	if ( ! isset ( $_POST['folder_id'] ) ) {
		wp_send_json_error( 'Folder ID not provided' );
	}

	// Get folder ID and template ID from request
	$folder_id = sanitize_text_field( $_POST['folder_id'] );
	$template_id = sanitize_text_field( $_POST['template_id'] );
	// Update template folder
	$moveTemplate = update_template_wizard_user_folder( $template_id, $folder_id );

	wp_send_json_success( $moveTemplate );

}
//Update the post meta that holds the folder id on the template post
function update_template_wizard_user_folder( $template_id, $folder_id ) {
	return boolval( update_post_meta( $template_id, 'user_folder', $folder_id ) );
}






// CRUD Operations

add_action( 'wp_ajax_update_wizard_user_folder_name', 'ajax_update_wizard_user_folder_name' );
function ajax_update_wizard_user_folder_name() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wizard_security' ) ) {
		wp_die( 'Invalid nonce' );
	}

	// Check if the parent ID is provided
	if ( ! isset ( $_POST['folder_id'] ) ) {
		wp_send_json_error( 'Folder rename failed: folder id not provided' );
	}

	// Check if the folder name is provided
	if ( ! isset ( $_POST['folder_name'] ) ) {
		wp_send_json_error( 'Folder rename  failed: no folder name provided' );
	}

	$user_id = get_current_user_id();
	$folder_id = sanitize_text_field( $_POST['folder_id'] );
	$new_name = sanitize_text_field( $_POST['folder_name'] );


	$renameFolder = update_wizard_user_folder_name( $user_id, $folder_id, $new_name );

	// Send back bool value for update success or not
	// False indicates the value is the same as before
	wp_send_json_success( $renameFolder );

}
function update_wizard_user_folder_name( $user_id, $folder_id, $new_name ) {
	$user_folders = get_wizard_user_folders( $user_id );

	foreach ( $user_folders as &$folder ) {
		if ( $folder['id'] === $folder_id ) {
			$folder['name'] = $new_name;
			break;
		}
	}

	// Return a bool value where true = success (or create) and false = failed
	return boolval( update_user_meta( $user_id, 'user_folders', json_encode( $user_folders ) ) );
}

add_action( 'wp_ajax_delete_wizard_user_folder', 'ajax_delete_wizard_user_folder' );

function ajax_delete_wizard_user_folder() {
	// Check nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wizard_security' ) ) {
		wp_die( 'Invalid nonce' );
	}

	// Check for folder_id
	if ( ! isset ( $_POST['folder_id'] ) ) {
		wp_send_json_error( 'Folder deletion failed: folder id not provided' );
	}

	$user_id = get_current_user_id();
	$folder_id = sanitize_text_field( $_POST['folder_id'] );

	$deleteFolder = delete_wizard_user_folder( $user_id, $folder_id );

	// Send back bool value for delete success or not
	wp_send_json_success( $deleteFolder );
}
function delete_wizard_user_folder( $user_id, $folder_id ) {
	$user_folders = get_wizard_user_folders( $user_id );
	$parent_id = null;

	// Find the parent ID for the folder being deleted
	foreach ( $user_folders as $folder ) {
		if ( $folder['id'] === $folder_id ) {
			$parent_id = $folder['parent_id'];
			break;
		}
	}

	// Move templates to the parent folder
	$templates_in_folder = get_templates_in_wizard_user_folder( $folder_id );
	foreach ( $templates_in_folder as $template ) {
		update_template_wizard_user_folder( $template->ID, $parent_id );
	}

	// Update child folders to have the deleted folder's parent as their new parent
	foreach ( $user_folders as $folder ) {
		if ( $folder['parent_id'] === $folder_id ) {
			// This child folder's parent is the folder being deleted, so update its parent_id
			edit_wizard_user_folder( $user_id, $folder['id'], [ 'parent_id' => $parent_id ] );
		}
	}

	// Remove the folder from the user's folder structure
	$user_folders = array_filter( $user_folders, function ($folder) use ($folder_id) {
		return $folder['id'] !== $folder_id;
	} );

	// Return bool val of usermeta update
	return boolval( update_user_meta( $user_id, 'user_folders', json_encode( array_values( $user_folders ) ) ) );
}


function edit_wizard_user_folder( $user_id, $folder_id, $attributes ) {
	$user_folders = get_wizard_user_folders( $user_id );

	foreach ( $user_folders as &$folder ) {
		if ( $folder['id'] === $folder_id ) {
			foreach ( $attributes as $key => $value ) {
				$folder[ $key ] = $value;
			}
			break;
		}
	}

	update_user_meta( $user_id, 'user_folders', json_encode( $user_folders ) );
}


add_action( 'wp_ajax_move_wizard_user_folder', 'ajax_move_wizard_user_folder' );

function ajax_move_wizard_user_folder() {
	// Check nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wizard_security' ) ) {
		wp_die( 'Invalid nonce' );
	}

	// Check for required params
	if ( ! isset ( $_POST['folder_id'] ) ) {
		wp_send_json_error( 'Folder move failed: folder id not provided' );
	}
	if ( ! isset ( $_POST['new_parent_id'] ) ) {
		wp_send_json_error( 'Folder move failed: new parent id not provided' );
	}

	$user_id = get_current_user_id();
	$folder_id = sanitize_text_field( $_POST['folder_id'] );
	$new_parent_id = sanitize_text_field( $_POST['new_parent_id'] );

	$moveFolder = move_wizard_user_folder( $user_id, $folder_id, $new_parent_id );

	wp_send_json_success( $moveFolder );
}
function move_wizard_user_folder( $user_id, $folder_id, $new_parent_id ) {
	// First, retrieve the user's current folder structure
	$user_folders = get_wizard_user_folders( $user_id );

	// Prevent moving a folder into itself or into one of its subfolders
	$subfolder_ids = get_wizard_user_subfolder_ids( $user_folders, $folder_id );
	if ( in_array( $new_parent_id, $subfolder_ids ) || $folder_id === $new_parent_id ) {
		// Optionally, handle this with an error or notification to the user
		return false;
	}

	// Update the parent ID for the folder being moved
	foreach ( $user_folders as &$folder ) {
		if ( $folder['id'] === $folder_id ) {
			$folder['parent_id'] = $new_parent_id;
			break;
		}
	}

	// Save the updated folder structure back to user meta
	$move_folder = boolval( update_user_meta( $user_id, 'user_folders', json_encode( $user_folders ) ) );
	return $move_folder;
}

// Helper function to recursively find all subfolder IDs for a given folder
function get_wizard_user_subfolder_ids( $folders, $parent_id, &$subfolder_ids = null, $recursive = true ) {
	if ( is_null( $subfolder_ids ) ) {
		$subfolder_ids = [];
	}

	foreach ( $folders as $folder ) {
		if ( $folder['parent_id'] === $parent_id ) {
			$subfolder_ids[] = $folder['id'];
			if ( $recursive ) {
				get_wizard_user_subfolder_ids( $folders, $folder['id'], $subfolder_ids, $recursive ); // Recursive call
			}
		}
	}
	return $subfolder_ids;
}






