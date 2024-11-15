<?php
function create_wizard_user_role() {
	$capabilities = array(
		'read' => true,
		'edit_posts' => true,
		'delete_posts' => true,
		'edit_published_posts' => true,
		'delete_published_posts' => true,
		'upload_files' => true,
		'edit_attachments' => true,
		'delete_attachments' => true,
	);

	$role = add_role( 'wizard_user', 'Wizard User', $capabilities );

	if ( ! $role instanceof WP_Role ) {
		return false;
	}

	return true;
}
add_action( 'init', 'create_wizard_user_role' );

//Init new wizard user
add_action('user_register', 'wizard_user_register_actions');
function wizard_user_register_actions($user_id) {
	set_new_user_as_wizard($user_id);
}

function set_new_user_as_wizard( $user_id ) {
	$user = new WP_User( $user_id );
	$user->set_role( 'wizard_user' );
}

//Keep wizard users out of admin
add_action('admin_init', 'redirect_wizard_user_from_admin');
function redirect_wizard_user_from_admin() {
	if ( current_user_can( 'wizard_user' ) && ! current_user_can( 'administrator' ) && is_admin() ) {
		wp_redirect( home_url() );
		exit;
	}
}

add_action('after_setup_theme', 'remove_admin_bar_for_wizard_user');
function remove_admin_bar_for_wizard_user()
{
	if (current_user_can('wizard_user') && ! current_user_can('administrator')) {
		show_admin_bar(false);
	}
}