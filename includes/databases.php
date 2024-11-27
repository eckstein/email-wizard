<?php
function create_wizard_tables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create wiz_templates table
    $table_name = $wpdb->prefix . 'wiz_templates';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) UNSIGNED NOT NULL,
        template_data longtext NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY post_id (post_id)
    ) $charset_collate;";
    dbDelta($sql);

    // Teams table
    $table_name = $wpdb->prefix . 'teams';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        avatar varchar(255),
        created_by bigint(20) UNSIGNED NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX created_by (created_by),
        INDEX status (status)
    ) $charset_collate;";
    dbDelta($sql);

    // Team members table
    $table_name = $wpdb->prefix . 'team_members';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        team_id bigint(20) NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        role varchar(50) NOT NULL DEFAULT 'member',
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_membership (team_id, user_id),
        INDEX team_id (team_id),
        INDEX user_id (user_id),
        INDEX status (status)
    ) $charset_collate;";
    dbDelta($sql);

    // User folders table 
    $table_name = $wpdb->prefix . 'user_folders';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        parent_id bigint(20),
        created_by bigint(20) UNSIGNED NOT NULL,
        team_id bigint(20),        
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX team_id_idx (team_id),
        INDEX created_by (created_by),
        INDEX parent_id (parent_id)
    ) $charset_collate;";
    dbDelta($sql);

    // Set the initial schema version
    update_option('wizard_db_version', '1.0');

    // Error logging
    if (!empty($wpdb->last_error)) {
        error_log('Database creation error: ' . $wpdb->last_error);
    }
}

register_activation_hook(__FILE__, 'create_wizard_tables');
