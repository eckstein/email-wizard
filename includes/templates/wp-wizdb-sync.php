<?php
add_action('wp_insert_post', 'add_to_wiz_templates_db', 10, 1);
function add_to_wiz_templates_db($post_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wiz_templates';

    if (get_post_type($post_id) == 'wiz_template' && get_post_status($post_id) == 'publish') {
        // Check if an entry already exists for this post ID
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id));

        // If an entry does not exist, insert a new row
        if ($exists == 0) {
            $post_author = get_post_field('post_author', $post_id);
            $data = array(
                'post_id' => $post_id,
                'user_id' => $post_author,
                'template_data' => '', // Initialize with empty template data
            );
            $wpdb->insert($table_name, $data);

            // Add template to root folder
            update_post_meta($post_id, 'wizard_folder', 'root');
        }
    }
}



// When a template post is moved to trash
add_action('wp_trash_post', 'update_wiz_template_status_trashed', 10, 1);
function update_wiz_template_status_trashed($post_id)
{
    if (get_post_type($post_id) == 'wiz_template') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wiz_templates';
        $wpdb->update(
            $table_name,
            array('status' => 'trashed'),
            array('post_id' => $post_id)
        );
    }
}

// When permanatly deleted, remove row from table, and delete all post meta
add_action('before_delete_post', 'delete_wiz_template_row', 10, 1);
function delete_wiz_template_row($post_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wiz_templates';

    if (get_post_type($post_id) == 'wiz_template') {
        $wpdb->delete(
            $table_name,
            array('post_id' => $post_id)
        );
    }
    // Delete all post meta
    $wpdb->delete($wpdb->postmeta, array('post_id' => $post_id));
    // clean up post cache
    clean_post_cache($post_id);
}

add_action('untrashed_post', 'update_wiz_template_status_restored', 10, 1);
// When template is restored from trash
function update_wiz_template_status_restored($post_id)
{
    $user_id = get_current_user_id();
    global $wpdb;
    $table_name = $wpdb->prefix . 'wiz_templates';
    if (get_post_type($post_id) == 'wiz_template') {
        $wpdb->update(
            $table_name,
            array('status' => 'active'),
            array('post_id' => $post_id)
        );
    }

    // Check if the folder id on this post still exists in the user's folder structure
    $folderManager = new WizardFolders($user_id);
    $user_folders = $folderManager->get_folders();
    $folder_id = get_post_meta($post_id, 'wizard_folder', true);
    $folder_found = false;
    foreach ($user_folders as $folder) {
        if ($folder['id'] == $folder_id) {
            $folder_found = true;
            break;
        }
    }
    // If folder no longer exists, move template to root
    if (! $folder_found) {
        update_post_meta($post_id, 'wizard_folder', 'root');
    }

    // Publish template (since restored posts are auto-drafted)
    wp_publish_post($post_id);
}
