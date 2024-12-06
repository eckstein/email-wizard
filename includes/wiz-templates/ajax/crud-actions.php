<?php

add_action('wiz_ajax_restore_trashed_templates', 'restore_trashed_templates_ajax');
function restore_trashed_templates_ajax()
{
    $decoded_template_ids = json_decode(stripslashes($_POST['template_ids'] ?? '[]'), true);

    if (empty($decoded_template_ids)) {
        wp_send_json(['success' => false, 'error' => 'Invalid or empty template IDs']);
    }

    $template_ids = array_map('intval', $decoded_template_ids);
    $results = [
        'success' => true,
        'restored' => [],
        'not_restored' => []
    ];

    foreach ($template_ids as $template_id) {
        $restore_result = wp_untrash_post($template_id);
        if ($restore_result) {
            $results['restored'][] = (int)$template_id;
        } else {
            $results['not_restored'][] = (int)$template_id;
        }
    }
    
    $results['success'] = !empty($results['restored']);
    wp_send_json($results);
}

add_action('wiz_ajax_delete_templates_forever', 'delete_templates_forever_ajax');
function delete_templates_forever_ajax()
{
    $decoded_template_ids = json_decode(stripslashes($_POST['template_ids'] ?? '[]'), true);
    if (empty($decoded_template_ids)) {
        wp_send_json(['success' => false, 'error' => 'Invalid or empty template IDs']);
    }

    $template_ids = array_map('intval', $decoded_template_ids);
    $results = [
        'success' => true,
        'deleted' => [],
        'not_deleted' => []
    ];

    foreach ($template_ids as $template_id) {
        $delete_result = wp_delete_post($template_id, true);
        if ($delete_result) {
            $results['deleted'][] = (int)$template_id;
        } else {
            $results['not_deleted'][] = (int)$template_id;
        }
    }
    
    $results['success'] = !empty($results['deleted']);
    wp_send_json($results);
}

function create_new_template_ajax()
{
    $wizard = WizardTemplateManager::get_instance();
    $template_name = sanitize_text_field($_POST['template_name'] ?? 'untitled');
    $folder_id = sanitize_text_field($_POST['folder_id'] ?? WizardTemplateManager::ROOT_FOLDER);
    
    $new_template = $wizard->create_template($template_name, $folder_id);
    if (is_wp_error($new_template)) {
        wp_send_json_error($new_template->get_error_message());
    }
    
    $template_data = $wizard->get_template($new_template);
    wp_send_json_success($template_data);
}
add_action('wiz_ajax_create_new_template', 'create_new_template_ajax');

function ajax_duplicate_wizard_template()
{
    if (!isset($_POST['template_id'])) {
        wp_send_json_error('Template id was not provided');
    }

    $wizard = WizardTemplateManager::get_instance();
    $template_id = sanitize_text_field($_POST['template_id']);
    $duplicate_id = $wizard->duplicate_template($template_id);
    
    if (!$duplicate_id) {
        wp_send_json_error('Failed to duplicate template');
    }
    
    $template_data = $wizard->get_template($duplicate_id);
    wp_send_json_success($template_data);
}
add_action('wiz_ajax_duplicate_wizard_template', 'ajax_duplicate_wizard_template');




