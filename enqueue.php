<?php
add_action('wp_enqueue_scripts', 'email_wizard_enqueue_assets');
function email_wizard_enqueue_assets()
{

	// Enqueue CSS bundle
	wp_enqueue_style('emailwizard-style', plugins_url('assets/dist/styles.css', __FILE__), array(), EMAILWIZARD_VERSION);

	// Enqueue script bundle
	wp_enqueue_script('emailwizard-bundle', plugins_url('assets/dist/index.js', __FILE__) . '?v=' . time(), array('jquery'), EMAILWIZARD_VERSION, true);
}


add_action('wp_head', 'email_wizard_localize_script_bundle');
function email_wizard_localize_script_bundle()
{
	email_wizard_localize_script('emailwizard-bundle');
};
function email_wizard_localize_script($scriptName)
{
	$currentUser = wp_get_current_user();
	$currentUserId = get_current_user_id();
	$folderHandler = new WizardFolders($currentUserId);
	$userFolders = $folderHandler->get_folders();
	$folder_id = isset($_GET['folder_id']) ? sanitize_text_field($_GET['folder_id']) : 'root';
	$subFolderIds = $folderHandler->get_subfolder_ids($folder_id, false);
	$recursiveSubFolderIds = $folderHandler->get_subfolder_ids($folder_id, true);

	$teamsHandler = new WizardTeams();
	$currentUserTeam = $teamsHandler->get_active_team($currentUserId);
	$currentTeamName = $teamsHandler->get_active_team_name($currentUserId);

	$nonce = wp_create_nonce('wizard_security_'.$currentUserTeam);

	$localized_data = array(
		'nonce' => $nonce,
		'ajaxurl' => home_url('/wiz-ajax/'),
		'currentPost' => get_post(get_the_ID()),
		'stylesheet' => plugins_url('', __FILE__),
		'plugin_url' => plugin_dir_url(__FILE__),
		'site_url' => get_bloginfo('url'),
		'current_user' => $currentUser,
		'current_user_id' => $currentUserId,
		'current_folder_id' => $folder_id,
		'current_user_folders' => $userFolders,
		'subfolder_ids' => $subFolderIds,
		'recursive_subfolder_ids' => $recursiveSubFolderIds,
		'active_team' => $currentUserTeam,
		'active_team_name' =>  $currentTeamName
	);
	wp_localize_script($scriptName, 'wizard', $localized_data);
}
