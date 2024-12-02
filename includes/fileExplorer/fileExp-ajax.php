
<?php
add_action ('wiz_ajax_generate_template_table_part', 'ajax_generate_template_table_part');

function ajax_generate_template_table_part()
{
    $part = isset($_POST['part']) ? sanitize_text_field($_POST['part']) : '';
    $current_folder = isset($_POST['current_folder']) ? sanitize_text_field($_POST['current_folder']) : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $item_id = isset($_POST['item_id']) ? sanitize_text_field($_POST['item_id']) : null;
    
    // Get args from POST and decode
    $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : [];

    $result = generate_template_table_part($part, $current_folder, $user_id, $item_id, $args);

    if (isset($result['error'])) {
        wp_send_json_error($result['error']);
    } else {
        wp_send_json_success($result);
    }
}
function generate_template_table_part($part, $current_folder, $user_id, $item_id = null, $args = [])
{
    $validParts = ['folder_actions', 'header', 'subfolders', 'templates', 'folder_row', 'template_row', 'body', 'full'];
    if (!in_array($part, $validParts)) {
        return ['error' => 'Invalid part name passed'];
    }
    if (!$user_id || !$current_folder) {
        return ['error' => 'Missing user or folder id'];
    }

    $template_table = new WizardTemplateTable($user_id, $current_folder, $args);

    $html = $template_table->render($part, $item_id);
    return $html ? ['html' => $html] : ['error' => 'Failed to generate HTML'];
}

add_action('wiz_ajax_generate_wizard_folder_breadcrumb', 'ajax_generate_wizard_folder_breadcrumb');
function ajax_generate_wizard_folder_breadcrumb() {
    $user_id = get_current_user_id();
    $current_folder = $_POST['current_folder'] ?? false;
    
    // Create template table instance
    $template_table = new WizardTemplateTable($user_id, $current_folder);
    
    // Generate breadcrumb using the class method
    $breadcrumbHtml = $template_table->generate_folder_breadcrumb($current_folder);
    
    if (!$breadcrumbHtml) {
        wp_send_json_error('Failed to generate breadcrumb HTML');
    }
    wp_send_json_success($breadcrumbHtml);
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


add_action('wiz_ajax_handle_template_folder_action', 'handle_template_folder_action_ajax');

function handle_template_folder_action_ajax()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('You do not have permission to perform this action.');
        return;
    }

    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $items = isset($_POST['items']) ? json_decode(stripslashes($_POST['items']), true) : [];

    if (!in_array($action_type, ['move', 'delete']) || !is_array($items)) {
        wp_send_json_error('Invalid action type or items data.');
        return;
    }

    $results = handle_template_folder_action($action_type, $items);

    if ($results['success']) {
        wp_send_json_success($results);
    } else {
        wp_send_json_error($results);
    }
}

function handle_template_folder_action($action_type, $items)
{
    if (empty($items)) {
        return [
            'success' => false,
            'error' => 'Empty items array'
        ];
    }

    $results = [
        'success' => false,
        'folders' => [],
        'templates' => []
    ];

    try {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        if ($action_type === 'move') {
            $results['folders'] = move_wizard_items('folder', $items['folders'] ?? []);
            $results['templates'] = move_wizard_items('template', $items['templates'] ?? []);
        } elseif ($action_type === 'delete') {
            $results['folders'] = delete_wizard_items('folder', $items['folders'] ?? []);
            $results['templates'] = delete_wizard_items('template', $items['templates'] ?? []);
        }

        $wpdb->query('COMMIT');
        $results['success'] = true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        $results['success'] = false;
        $results['error'] = $e->getMessage();
    }

    return $results;
}

function move_wizard_items($item_type, $moves)
{
    if (empty($moves)) {
        return [
            'success' => false,
            'error' => "Invalid or empty {$item_type} moves",
            'moved' => [],
            'not_moved' => [],
            'not_found' => []
        ];
    }

    $results = [
        'success' => false,
        'error' => null,
        'moved' => [],
        'not_moved' => [],
        'not_found' => []
    ];

    $user_id = get_current_user_id();

    foreach ($moves as $item_id => $new_parent_id) {
        if (!$item_id || !$new_parent_id) {
            $results['not_moved'][] = (int)$item_id;
            $results['error'] = "Invalid item ID or parent ID";
            continue;
        }

        if ($item_type === 'folder') {
            $folderManager = new WizardFolders($user_id);
            $move_result = $folderManager->move_folder($item_id, $new_parent_id);
            
            if (is_wp_error($move_result)) {
                $results['not_moved'][] = (int)$item_id;
                $results['error'] = $move_result->get_error_message();
            } else {
                $results['moved'][] = (int)$item_id;
            }
        } elseif ($item_type === 'template') {
            $post = get_post($item_id);
            if (!$post) {
                $results['not_found'][] = (int)$item_id;
                $results['error'] = "Template not found";
                continue;
            }
            
            $update_result = update_post_meta($item_id, 'wizard_folder', $new_parent_id);
            if ($update_result !== false) {
                $results['moved'][] = (int)$item_id;
            } else {
                $results['not_moved'][] = (int)$item_id;
                $results['error'] = "Failed to update template folder";
            }
        }
    }

    $results['success'] = !empty($results['moved']) && empty($results['error']);
    return $results;
}
function delete_wizard_items($item_type, $deletes) {
    if (empty($deletes)) {
        return [
            'success' => false,
            'error' => "Invalid or empty {$item_type} deletes",
            'deleted' => [],
            'not_deleted' => [],
            'not_found' => [],
            'moved_templates' => [],
            'moved_subfolders' => []
        ];
    }

    $results = [
        'success' => false,
        'error' => null,
        'deleted' => [],
        'not_deleted' => [],
        'not_found' => [],
        'moved_templates' => [],
        'moved_subfolders' => []
    ];

    $user_id = get_current_user_id();

    if ($item_type === 'folder') {
        foreach ($deletes as $folder_id) {
            $folderManager = new WizardFolders($user_id);
            $thisFolder = $folderManager->get_folder($folder_id);
            
            if (!$thisFolder) {
                $results['not_found'][] = (int)$folder_id;
                $results['error'] = "Folder not found";
                continue;
            }

            $parent_id = $thisFolder['parent_id'];
            $delete_result = $folderManager->delete_folder($folder_id);

            if (is_wp_error($delete_result)) {
                $results['not_deleted'][] = (int)$folder_id;
                $results['error'] = $delete_result->get_error_message();
                continue;
            }

            $templates_in_folder = $folderManager->get_templates_in_folder($folder_id);
            if (!empty($templates_in_folder)) {
                $template_moves = [];
                foreach ($templates_in_folder as $template) {
                    $template_moves[$template['id']] = $parent_id;
                }
                $move_result = move_wizard_items('template', $template_moves);
                $results['moved_templates'] = array_merge($results['moved_templates'], $move_result['moved']);
            }

            $results['deleted'][] = (int)$folder_id;
        }
    } elseif ($item_type === 'template') {
        foreach ($deletes as $template_id) {
            $post = get_post($template_id);
            if (!$post) {
                $results['not_found'][] = (int)$template_id;
                $results['error'] = "Template not found";
                continue;
            }

            $delete_result = wp_trash_post($template_id);
            if ($delete_result) {
                $results['deleted'][] = (int)$template_id;
            } else {
                $results['not_deleted'][] = (int)$template_id;
                $results['error'] = "Failed to trash template";
            }
        }
    }

    $results['success'] = !empty($results['deleted']) && empty($results['error']);
    return $results;
}


add_action('wiz_ajax_get_templates_in_wizard_user_folders', 'ajax_get_templates_in_wizard_user_folders');
function ajax_get_templates_in_wizard_user_folders()
{
    // Get the current user ID
    $user_id = get_current_user_id();
    $current_folder_id = isset($_POST['current_folder_id']) ? sanitize_text_field($_POST['current_folder_id']) : null;
    if (empty($current_folder_id)) {
        wp_send_json_error('No folder id provided');
    }
    $folderManager = new WizardFolders($user_id);

    // get all folder IDs from this folder and its subfolders
    $folder_ids = $folderManager->get_subfolder_ids($current_folder_id);
    $folder_ids[] = $current_folder_id;

    $templates = $folderManager->get_templates_in_folder($folder_ids, true);

    if (!empty($templates)) {
        $formatted_templates = array_map(function ($template) {
            $templateManager = new WizardTemplateManager();
            $wizTemplate = $templateManager->get_template($template->ID);
            $template->post_modified = $wizTemplate['last_updated'];
            return [
                'ID' => $template->ID,
                'post_title' => $template->post_title,
                'post_modified' => $template->post_modified,
                'permalink' => get_permalink($template->ID),
                'last_updated' => get_the_modified_time('U', $template->ID)
            ];
        }, $templates);
        wp_send_json_success($formatted_templates);
    } else {
        wp_send_json_success([]);
    }
}



add_action('wiz_ajax_update_wizard_user_folder_name', 'ajax_update_wizard_user_folder_name');
function ajax_update_wizard_user_folder_name()
{

    // Check if the parent ID is provided
    if (! isset($_POST['folder_id'])) {
        wp_send_json_error('Folder rename failed: folder id not provided');
    }

    // Check if the folder name is provided
    if (! isset($_POST['folder_name'])) {
        wp_send_json_error('Folder rename  failed: no folder name provided');
    }

    $user_id = get_current_user_id();
    $folder_id = sanitize_text_field($_POST['folder_id']);
    $new_name = sanitize_text_field($_POST['folder_name']);

    $user_folders = new WizardFolders($user_id);
    $renameFolder = $user_folders->edit_folder($folder_id, ['name' => $new_name]);
    if (!$renameFolder) {
        wp_send_json_error('Folder rename failed: folder not found');
    }

    wp_send_json_success($renameFolder);
}

add_action('wiz_ajax_get_wizard_user_folders', 'ajax_get_wizard_user_folders');
function ajax_get_wizard_user_folders() {
    $exclude = isset($_POST['exclude']) ? json_decode(stripslashes($_POST['exclude']), true) : [];
    $folders = get_wizard_user_folders($exclude);
    if (is_wp_error($folders)) {
        wp_send_json_error($folders->get_error_message());
    }
    wp_send_json_success($folders);
}
function get_wizard_user_folders($exclude = []) {
    $user_id = get_current_user_id();
    $user_folders = new WizardFolders($user_id);
    $folders = $user_folders->get_folders($exclude);
    return $folders;
}


