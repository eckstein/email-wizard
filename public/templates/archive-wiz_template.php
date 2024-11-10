<?php
get_header();

if (! is_user_logged_in()) {
	wp_login_form();
	return;
}

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;

// Base template page ID
$templateArchiveLink = get_post_type_archive_link('wiz_template');

// Initialize folder manager
$folderManager = new WizardFolders($current_user_id);

// Get current folder ID from URL
$folder_id = isset($_GET['folder_id']) ? sanitize_text_field($_GET['folder_id']) : 'root';

// Handle trash folder case
if ($folder_id == 'root') {
	$current_folder = 'root';
	$folder_exists = true;
} else if ($folder_id === 'trash') {
	$current_folder = 'trash';
	$folder_exists = true;
} else {
	// Get folder and verify existence + permissions in one call
	$folder = $folderManager->get_folder($folder_id);
	$folder_exists = ($folder !== null);
	$current_folder = $folder_exists ? $folder_id : 'root';
}

// Get templates in this folder
$folder_templates = $folderManager->get_templates_in_folder($current_folder);

// Get immediate subfolders using the folder manager
$subfolders = $folderManager->get_subfolders($current_folder, false);
$subfolder_ids = array_column($subfolders, 'id');

?>

<div id="user-folder-ui" data-current_folder="<?php echo $current_folder; ?>">
	
	<?php
	if (!$folder_exists) {
		echo '<div class="folder-no-access-message">This folder no longer exists or you do not have permission to view it!</div>';
	} else {
	?>
		<div id="user-folder-pane">

			<?php
			$tableArgs = [
				'sortBy' => $_GET['sortBy'] ?? 'last_updated',
			];
			if (isset($_GET['sort'])) {
				$tableArgs['sort'] = $_GET['sort'];
			}
			$table = new WizardTemplateTable($current_user_id, $folder_id, $tableArgs);
			echo $table->render();
			?>
		</div>
	<?php } ?>
</div>

<?php get_footer(); ?>