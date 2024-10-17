<?php
get_header();

if ( ! is_user_logged_in() ) {
	wp_login_form();
	return;
}

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;

//init_wizard_user_folders( $current_user_id );

// Base template page ID
$templateArchiveLink = get_post_type_archive_link('wiz_template');

// Get existing folder structure from usermeta
$user_folders = get_wizard_user_folders( $current_user_id );

//print_r( $user_folders );

// Get current folder
$folder_id = isset( $_GET['folder_id'] ) ? sanitize_text_field( $_GET['folder_id'] ) : 'root';

// Initiate sub-folder ID (to pass via reference)
$subfolderIds = null;
$subfolder_ids = get_wizard_user_subfolder_ids( $user_folders, $folder_id, $subfolderIds, false ); // Get direct children only

// Check if folder id from URL exists in folder meta and if it belongs to the user and set the current_folder variable
$current_folder = 'root';
$folder_exists = false;
foreach ( $user_folders as $folder ) {
	if ( $folder['id'] === $folder_id ) {
		$current_folder = $folder_id;
		$folder_exists = true;
		break;
	}
}

//delete_user_meta( get_current_user_id(), 'user_folders' );

// Get folder structure
$breadcrumb = generate_wizard_folder_breadcrumb( $folder_id, $user_folders );

// Get templates in this folder
$folder_templates = get_templates_in_wizard_user_folder( $folder_id );

?>

<div id="user-folder-ui">
	<div id="user-folder-breadcrumb">
		<?php echo $breadcrumb; ?>
		<div class="breadcrumb-actions">
			<button class="wizard-button create-wizard-folder" data-folder-id="<?php echo $folder_id; ?>"><i
					class="fa-solid fa-folder-plus"></i>&nbsp;&nbsp;Add
				Folder</button>
			<?php if ( $current_folder !== 'root' ) { ?>
				<button class="wizard-button move-folder" data-folder-id="<?php echo $folder_id; ?>"><i
						class="fa-solid fa-folder-tree"></i></button>

				<button class="wizard-button red delete-folder" data-folder-id="<?php echo $folder_id; ?>"><i
						class="fa-solid fa-trash"></i></button>
			<?php } ?>
		</div>
	</div>

	<div id="user-folder-pane">
		<div id="bulk-actions" class="disabled">
			<button class="wizard-button small" id="move-selected" disabled><i
					class="fa-solid fa-folder-tree"></i>&nbsp;&nbsp;Move</button>
			<button class="wizard-button small red" id="delete-selected" disabled><i
					class="fa-solid fa-trash"></i>&nbsp;&nbsp;Delete</button>
		</div>
		<table class="wizard-folders-table">
			<thead>
				<tr>
					<th class="wizard-table-bulk-check-all header"><input type="checkbox"></th>
					<th class="wizard-table-icon header"></th>
					<th class="wizard-table-template-name header">Template Name</th>
					<th class="wizard-table-last-modified header">Last Modified</th>
					<th class="wizard-table-template-actions header">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php

				// Sort subfolders by name
				$subfolders = array_filter( $user_folders, function ($folder) use ($subfolder_ids) {
					return in_array( $folder['id'], $subfolder_ids );
				} );

				usort( $subfolders, function ($a, $b) {
					return strcmp( $a['name'], $b['name'] );
				} );

				// Display subfolders
				foreach ( $subfolders as $subfolder ) {
					$folderPermalink = esc_url( add_query_arg( 'folder_id', $subfolder['id'], $templateArchiveLink ) );
					echo '<tr data-type="folder" data-id="' . $subfolder['id'] . '">';
					echo '<td class="wizard-table-bulk-check folder"><input type="checkbox" name="' . $subfolder['id'] . '_checkbox" value="' . $subfolder['id'] . '" data-type="folder"></td>';
					echo '<td class="wizard-table-icon folder"><i class="fa-solid fa-folder"></i></td>';
					echo '<td class="wizard-table-template-name"><a href="' . $folderPermalink . '">' . esc_html( $subfolder['name'] ) . '</a></td>';
					echo '<td class="wizard-table-last-modified"></td>';
					echo '<td class="wizard-table-template-actions"><div class="table-template-actions-inner">';
					echo '<i title="Edit folder title" class="fa-solid fa-pencil edit-folder-title" data-editable="' . $subfolder['id'] . '"></i>
					<i title="Move Folder" class="fa-solid fa-folder-tree move-folder" data-folder-id="' . $subfolder['id'] . '"></i>
					<i title="Delete Folder" class="fa-solid fa-trash delete-folder" data-folder-id="' . $subfolder['id'] . '"></i></div></td>';
					echo '</tr>';
				}

				// Display templates
				foreach ( $folder_templates as $template ) {
					$templatePermalink = get_the_permalink( $template->ID );
					echo '<tr data-type="template" data-id="' . $template->ID . '">';
					echo '<td class="wizard-table-bulk-check template"><input type="checkbox" name="' . $template->ID . '_checkbox"value="' . $template->ID . '" data-type="template"></td>';
					echo '<td class="wizard-table-icon template"><i class="fa-solid fa-file-code"></i></td>';
					echo '<td class="wizard-table-template-name"><a href="' . $templatePermalink . '">' . $template->post_title . '</a></td>';
					echo '<td class="wizard-table-last-modified"></td>';
					echo '<td class="wizard-table-template-actions"><div class="table-template-actions-inner">';
					echo '<i title="Duplicate Template" class="fa-solid fa-copy duplicate-template" data-template-id="' . $template->ID . '"></i>
					<i title="Move Template" class="fa-solid fa-folder-tree move-template" data-template-id="' . $template->ID . '"></i>
					<i title="Trash Template" class="fa-solid fa-trash delete-template" data-template-id="' . $template->ID . '"></i></div></td>';
					echo '</tr>';

				}
				?>
			</tbody>
		</table>
	</div>

</div>

<?php get_footer(); ?>