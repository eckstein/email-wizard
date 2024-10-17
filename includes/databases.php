<?php
function create_wiz_templates_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wiz_templates';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        post_id bigint(20) UNSIGNED NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        last_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) NOT NULL DEFAULT 'active',
        template_data longtext NOT NULL,
        PRIMARY KEY (post_id),
        INDEX user_id (user_id)
    ) $charset_collate;";

	require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}
register_activation_hook( __FILE__, 'create_wiz_templates_table' );

