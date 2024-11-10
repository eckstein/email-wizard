<?php
function create_wizard_tables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create wiz_templates table
    $table_name = $wpdb->prefix . 'wiz_templates';
    $sql = "CREATE TABLE $table_name (
        post_id bigint(20) UNSIGNED NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        last_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) NOT NULL DEFAULT 'active',
        template_data longtext NOT NULL,
        PRIMARY KEY (post_id),
        INDEX user_id (user_id)
    ) $charset_collate;";
    dbDelta($sql);

    // Teams table
    $table_name = $wpdb->prefix . 'teams';
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql);

    // User folders table 
    $table_name = $wpdb->prefix . 'user_folders';
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        parent_id INT,
        created_by INT,
        team_id INT,        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY team_id_idx (team_id),
        PRIMARY KEY  (id)
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

// Save, check, and update the database schema to avoid having to deaticate/reactive the plugin for schema changes
add_action('plugins_loaded', 'check_and_update_db_schema');
function check_and_update_db_schema()
{
    $current_db_version = get_option('wizard_db_version', '0');

    if (version_compare($current_db_version, '1.0', '<')) {
        create_wizard_tables();
    }

    if (version_compare($current_db_version, '1.1', '<')) { // Call the "version_x_x function to update to the new scheme
        update_to_version_1_1();
    }

    // Add more version checks as your schema evolves
}

// When we update the scheme, put the database changes inside here and rename the function to the proper version based on what we put above
function update_to_version_1_1() 
{
    global $wpdb;

    // Example: Add a new column to wp_user_folders
    // $table_name = $wpdb->prefix . 'user_folders';
    // $wpdb->query("ALTER TABLE $table_name ADD COLUMN description TEXT");

    // update_option('wizard_db_version', '1.1');
}



