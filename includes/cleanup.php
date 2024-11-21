<?php

//Schedule the cleanup tasks
function schedule_cleanup_tasks()
{
    if (!wp_next_scheduled('cleanup_task')) {
        wp_schedule_event(time(), 'daily', 'cleanup_tasks');
    }
}
add_action('init', 'schedule_cleanup_tasks');

// Cleanup orphaned folders
add_action('cleanup_tasks', 'handle_orphaned_folders');
function handle_orphaned_folders() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_folders';
    // Find folders that have a parent_id that doesn't exist
    $orphaned_folders = $wpdb->get_results("SELECT * FROM $table_name WHERE parent_id NOT IN (SELECT id FROM $table_name)");
    $orphansMoved = [];
    foreach ($orphaned_folders as $folder) {
        // change the parent name to null to move it to the root folder
        $orphansMoved[] = $wpdb->update($table_name, array('parent_id' => null), array('id' => $folder->id));
    }
    return $orphansMoved;
}




