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
	set_user_email_as_username($user_id);
	set_default_user_avatar($user_id);
	create_default_user_team($user_id);
}

function set_new_user_as_wizard( $user_id ) {
	$user = new WP_User( $user_id );
	$user->set_role( 'wizard_user' );
}
function create_default_user_team($user_id) {
	$teamsManager = new WizardTeams();
	$newTeamId = $teamsManager->create_team(['name' => 'My Team', 'created_by' => $user_id]);
	// Set user as admin role
	$teamsManager->add_team_member($newTeamId, $user_id, 'admin');
	// Set new team as active team
	$teamsManager->switch_active_team($user_id, $newTeamId);
}
function set_user_email_as_username($user_id) {
	$user = new WP_User($user_id);
	$user->set_username($user->user_email);
}

add_action('after_setup_theme', 'remove_admin_bar_for_wizard_user');
function remove_admin_bar_for_wizard_user()
{
	if (current_user_can('wizard_user') && ! current_user_can('administrator')) {
		show_admin_bar(false);
	}
}

function set_default_user_avatar($user_id) {
	$default_avatar_id = get_option('wizard_default_avatar');
	if ($default_avatar_id) {
		update_user_meta($user_id, 'local_avatar', $default_avatar_id);
	}
}