<?php

add_action('wiz_ajax_restore_trashed_templates', 'restore_trashed_templates_ajax');
function restore_trashed_templates_ajax()
{
    $template_ids = isset($_POST['template_ids']);

    $decoded_template_ids = json_decode(stripslashes($_POST['template_ids']), true);

    if (empty($decoded_template_ids)) {
        wp_send_json(['success' => false, 'error' => 'Invalid or empty template IDs']);
    }
    $template_ids = array_map('intval', $decoded_template_ids);
    $results = restore_trashed_templates($template_ids);
    wp_send_json($results);
}
function restore_trashed_templates($template_ids)
{
    if (empty($template_ids)) {
        return ['success' => false, 'error' => 'Invalid or empty template IDs'];
    }
    $results = [
        'success' => true,
        'restored' => [],
        'not_restored' => []
    ];
    foreach ($template_ids as $template_id) {
        $post = get_post($template_id);
        if (!$post) {
            $results['not_restored'][] = (int)$template_id;
            continue;
        }
        $restore_result = wp_untrash_post($template_id);
        if ($restore_result) {
            $results['restored'][] = (int)$template_id;
        } else {
            $results['not_restored'][] = (int)$template_id;
        }
    }
    $results['success'] = !empty($results['restored']);
    return $results;
}

add_action('wiz_ajax_delete_templates_forever', 'delete_templates_forever_ajax');
function delete_templates_forever_ajax()
{
    $template_ids = isset($_POST['template_ids']);

    $decoded_template_ids = json_decode(stripslashes($_POST['template_ids']), true);
    if (empty($decoded_template_ids)) {
        wp_send_json(['success' => false, 'error' => 'Invalid or empty template IDs']);
    }
    $template_ids = array_map('intval', $decoded_template_ids);
    $results = delete_templates_forever($template_ids);
    wp_send_json($results);
}
function delete_templates_forever($template_ids)
{
    if (empty($template_ids)) {
        return ['success' => false, 'error' => 'Invalid or empty template IDs'];
    }
    $results = [
        'success' => true,
        'deleted' => [],
        'not_deleted' => []
    ];
    foreach ($template_ids as $template_id) {
        $post = get_post($template_id);
        if (!$post) {
            $results['not_deleted'][] = (int)$template_id;
            continue;
        }
        $delete_result = wp_delete_post($template_id, true);
        if ($delete_result) {
            $results['deleted'][] = (int)$template_id;
        } else {
            $results['not_deleted'][] = (int)$template_id;
        }
    }
    $results['success'] = !empty($results['deleted']);
    return $results;
}



// Add folder on ajax request
add_action('wiz_ajax_add_wizard_user_folder', 'ajax_add_wizard_user_folder');
function ajax_add_wizard_user_folder()
{

    // Check if the parent ID is provided
    if (! isset($_POST['parent_id'])) {
        wp_send_json_error('Folder creation failed: parent folder not found');
    }

    // Check if the folder name is provided
    if (! isset($_POST['folder_name'])) {
        wp_send_json_error('Folder creation failed: no folder name provided');
    }

    // Sanitize the input data
    $parent_id = sanitize_text_field($_POST['parent_id']);
    if ($parent_id == 'root') {
        $parent_id = null;
    }
    $folder_name = sanitize_text_field($_POST['folder_name']);

    // Get the current user ID
    $user_id = get_current_user_id();

    // Add the new folder
    $folderManager = new WizardFolders($user_id);
    $newFolderId = $folderManager->add_folder($folder_name, $parent_id);

    // Send a success response with the new folder ID
    wp_send_json_success(['folder_id' => $newFolderId]);
}

function create_new_template_ajax()
{
    $wizard = new WizardTemplates();
    $template_name = $_POST['template_name'] ?? 'untitled';
    $folder_id = $_POST['folder_id'] ?? null;
    $new_template = $wizard->create_template($template_name, $folder_id);

    if (is_wp_error($new_template)) {
        wp_send_json_error($new_template->get_error_message());
    }
    wp_send_json_success($new_template);
}
add_action('wiz_ajax_create_new_template', 'create_new_template_ajax');

function ajax_duplicate_wizard_template()
{
    if (!isset($_POST['template_id'])) {
        wp_send_json_error('Template id was not provided');
    }

    $wizard = new WizardTemplates();
    $post_to_dupe = sanitize_text_field($_POST['template_id']);
    $duplicate_template = $wizard->duplicate_template($post_to_dupe);
    wp_send_json_success($duplicate_template);
}
add_action('wiz_ajax_duplicate_wizard_template', 'ajax_duplicate_wizard_template');

function ajax_search_wiz_templates()
{
    if (!isset($_POST['term'])) {
        wp_send_json_error('Term not provided');
    }

    $templateManager = new WizardTemplates();
    $term = sanitize_text_field($_POST['term']);
    $folderIds = isset($_POST['folderIds']) ? json_decode(stripslashes($_POST['folderIds']), true) : ['root'];
    $results = $templateManager->search_templates($term, $folderIds);

    if (!empty($results)) {
        wp_send_json_success($results);
    } else {
        wp_send_json_error('No results found');
    }
}
add_action('wiz_ajax_search_wiz_templates', 'ajax_search_wiz_templates');
