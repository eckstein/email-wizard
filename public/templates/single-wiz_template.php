<?php
get_header();
$postId = get_the_ID();

$current_user = wp_get_current_user();
$userId = $current_user->ID;

$folderManager = new WizardFolders($user_id);
$user_folders = $folderManager->get_folders();
$templateFolder = get_post_meta($postId, 'wizard_folder', true);

?>
<header class="wizHeader">
	<div class="wizHeaderInnerWrap">
		<div class="wizHeader-left">
			<div id="single-wizard-template-breadcrumb">
				<?php echo $breadcrumb = generate_wizard_folder_breadcrumb($templateFolder, $user_folders); ?>
			</div>
			<h1 id="single-template-title" class="entry-title" title="<?php echo get_the_title(); ?>" itemprop="name">
				<?php echo get_the_title($postId); ?>
			</h1>



		</div>
		<div class="wizHeader-right">
			<div class="wizHeader-actions">
				<div title="Duplicate Template" class="wizard-button green duplicate-template"
					data-template-id="<?php echo $postId; ?>">
					<i class="fa-solid fa-copy"></i>&nbsp;&nbsp;Duplicate
				</div>
				<div title="Move Template" class="wizard-button green move-template"
					data-template-id="<?php echo $postId; ?>">
					<i class="fa-solid fa-folder-tree"></i>&nbsp;&nbsp;Move
				</div>
				<div title="Delete Template" class="wizard-button red delete-template"
					data-template-id="<?php echo $postId; ?>">
					<i class="fa-solid fa-trash"></i>&nbsp;&nbsp;Trash
				</div>
			</div>
		</div>
	</div>
</header>

<div id="single-template-landing" class="entry-content" data-template-id="<?php echo $postId; ?>"
	itemprop="mainContentOfPage">
	<div id="template-data-pane">
		<div id="template-data-header">
			<h2>Template Data</h2>
			<div id="template-data-actions">
				<button class="wizard-button green save-template-data" data-template-id="<?php echo $postId; ?>"><i class="fa-solid fa-floppy-disk"></i>&nbsp;&nbsp;Save</button>
			</div>
		</div>
		<form id="template-data-form">
			<div class="wizard-form-content">

				<?php
				$sectionId = 'message-settings';
				$sectionTitle = 'Message Settings';
				$fieldData = [
					['id' => 'subject-line', 'type' => 'text', 'label' => 'Subject Line', 'value' => 'Hello, world!', 'disabled' => false],
					['id' => 'preview-text', 'type' => 'text', 'label' => 'Preview Text', 'value' => 'This is a preview.', 'disabled' => false],
					['id' => 'enable-notifications', 'type' => 'checkbox', 'label' => 'Enable Notifications', 'value' => true, 'disabled' => false],
					[
						'id' => 'favorite_fruits',
						'label' => 'Favorite Fruits',
						'type' => 'checkbox-group',
						'value' => ['apple', 'orange'],
						'options' => [
							['value' => 'apple', 'label' => 'Apple'],
							['value' => 'banana', 'label' => 'Banana'],
							['value' => 'orange', 'label' => 'Orange'],
							['value' => 'grape', 'label' => 'Grape'],
						],
						'disabled' => false

					],
					[
						'id' => 'gender',
						'label' => 'Gender',
						'type' => 'radio-group',
						'value' => 'female',
						'options' => [
							['value' => 'male', 'label' => 'Male'],
							['value' => 'female', 'label' => 'Female'],
							['value' => 'other', 'label' => 'Other'],
						],
						'disabled' => false
					],
					[
						'id' => 'font-family',
						'type' => 'select',
						'label' => 'Font Family',
						'value' => 'Arial',
						'options' => [
							['value' => 'Arial', 'label' => 'Arial'],
							['value' => 'Helvetica', 'label' => 'Helvetica'],
							['value' => 'Verdana', 'label' => 'Verdana'],
						],
						'disabled' => false
					],
					[
						'id' => 'my_repeater',
						'label' => 'My Repeater',
						'type' => 'repeater',
						'display' => 'flex',
						'fields' => [
							[
								'id' => 'utm_parameter',
								'label' => 'UTM Param',
								'type' => 'text',
								'title' => 'UTM Parameter',
								'value' => '',
								'placeholder' => 'utm_campaign',
							],
							[
								'id' => 'utm_value',
								'label' => 'UTM Value',
								'type' => 'text',
								'title' => 'UTM Value',
								'value' => '',
								'placeholder' => '1234567',
							],
						],
						'disabled' => false
					]
				];
				echo generate_template_data_fieldset($sectionId, $sectionTitle, $fieldData);


				?>
			</div>
		</form>
	</div>
	<div id="template-render">
		Template Render
	</div>
</div>

<?php
get_footer();
?>