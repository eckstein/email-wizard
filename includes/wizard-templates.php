<?php
// Get template from database
function get_wiztemplate( $postId ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wiz_templates';
	// Fetch the entire template data from the database
	$templateObject = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $table_name WHERE post_id = %d",
		$postId
	), ARRAY_A );

	return $templateObject;
}


add_action( 'wp_ajax_duplicate_wizard_template', 'ajax_duplicate_wizard_template' );
function ajax_duplicate_wizard_template() {
	// Verify the nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wizard_security' ) ) {
		wp_die( 'Invalid nonce' );
	}
    // check for post id
	if ( ! isset ( $_POST['template_id'] ) ) {
		wp_send_json_error( 'Template id was not provided' );
	}

    // Sanitize input
	$post_to_dupe = sanitize_text_field( $_POST['template_id'] );

	$duplicate_template = duplicate_wizard_template( $post_to_dupe );

	wp_send_json_success( $duplicate_template );
}
function duplicate_wizard_template($template_id) {
	$template = get_post( $template_id );
    $new_template = wp_insert_post( array(
		'post_title' => '(Copy) '.$template->post_title,
		'post_status' => 'publish',
		'post_type' => 'wiz_template',
	) );

    // Set folder to the same folder
	$folder = get_post_meta( $template_id, 'user_folder', true );
	update_post_meta( $new_template, 'user_folder', $folder );

	// Update new template data in custom database to match original template's template_data from custom database column
	global $wpdb;
	$table_name = $wpdb->prefix . 'wiz_templates';
	$template_data = $wpdb->get_var( $wpdb->prepare( "SELECT template_data FROM $table_name WHERE post_id = %d", $template_id ) );
	$wpdb->update(
		$table_name,
		array( 'template_data' => $template_data ),
		array( 'post_id' => $new_template )
	);

	return $new_template;

}
function add_wiz_template_row( $post_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wiz_templates';

	if ( get_post_type( $post_id ) == 'wiz_template' && get_post_status( $post_id ) == 'publish' ) {
		// Check if an entry already exists for this post ID
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id ) );

		// If an entry does not exist, insert a new row
		if ( $exists == 0 ) {
			$post_author = get_post_field( 'post_author', $post_id );
			$data = array(
				'post_id' => $post_id,
				'user_id' => $post_author,
				'template_data' => '', // Initialize with empty template data
			);
			$wpdb->insert( $table_name, $data );

			// Add template to root folder
			update_post_meta( $post_id, 'user_folder', 'root' );
		}
	}
}
add_action( 'wp_insert_post', 'add_wiz_template_row', 10, 1 );

// Delete a template via ajax
add_action( 'wp_ajax_delete_wizard_user_template', 'ajax_delete_wizard_template' );
function ajax_delete_wizard_template() {
    // verify nonce
    if (! wp_verify_nonce( $_POST['nonce'], 'wizard_security' ) ) {
        wp_die( 'Invalid nonce' );
    }
    // check for template ID
    if (! isset( $_POST['template_id'] ) ) {
        wp_send_json_error( 'Template ID not provided' );
    }
    // Sanitize the input data
    $template_id = sanitize_text_field( $_POST['template_id'] );

    // Delete post
    $deletePost = wp_trash_post( $template_id );
	if ( $deletePost ) {
		wp_send_json_success( 'Template deleted' );
	} else {
		wp_send_json_error( 'Template deletion failed' );
	}
}

// When a template post is moved to trash
function update_wiz_template_status_trashed( $post_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wiz_templates';

	if ( get_post_type( $post_id ) == 'wiz_template' ) {
		$wpdb->update(
			$table_name,
			array( 'status' => 'trashed' ),
			array( 'post_id' => $post_id )
		);
	}
}

// When permanatly deleted, remove row from table
add_action( 'wp_trash_post', 'update_wiz_template_status_trashed', 10, 1 );

function delete_wiz_template_row( $post_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wiz_templates';

	if ( get_post_type( $post_id ) == 'wiz_template' ) {
		$wpdb->delete(
			$table_name,
			array( 'post_id' => $post_id )
		);
	}
}
add_action( 'before_delete_post', 'delete_wiz_template_row', 10, 1 );

// When template is restored from trash
function update_wiz_template_status_restored( $post_id ) {
	$user_id = get_current_user_id();
	global $wpdb;
	$table_name = $wpdb->prefix . 'wiz_templates';
	if ( get_post_type( $post_id ) == 'wiz_template' ) {
		$wpdb->update(
			$table_name,
			array( 'status' => 'active' ),
			array( 'post_id' => $post_id )
		);
	}

	// Check if the folder id on this post still exists in the user's folder structure
	$user_folders = get_wizard_user_folders( $user_id );
	$folder_id = get_post_meta( $post_id, 'user_folder', true );
	$folder_found = false;
	foreach ( $user_folders as $folder ) {
		if ( $folder['id'] == $folder_id ) {
			$folder_found = true;
			break;
		}
	}
	// If folder no longer exists, move template to root
	if ( ! $folder_found ) {
		update_post_meta( $post_id, 'user_folder', 'root' );
	}

    // Publish template (since restored posts are auto-drafted)
	wp_publish_post( $post_id );
}
add_action( 'untrashed_post', 'update_wiz_template_status_restored', 10, 1 );