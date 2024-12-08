<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handle user avatar upload
 */
function handle_update_user_avatar() {
    if (!current_user_can('edit_user', $_POST['user_id'])) {
        wp_send_json_error('Permission denied');
    }

    if (empty($_FILES['avatar'])) {
        wp_send_json_error('No file uploaded');
    }

    $avatar = new WizardAvatar($_POST['user_id']);
    $result = $avatar->handle_upload($_FILES['avatar']);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    $set_result = $avatar->set_avatar($result);
    if (is_wp_error($set_result)) {
        wp_delete_attachment($result, true);
        wp_send_json_error($set_result->get_error_message());
    }

    wp_send_json_success([
        'message' => 'Avatar updated successfully',
        'avatar_url' => $avatar->get_avatar_url(96),
        'avatar_html' => $avatar->get_avatar(96)
    ]);
}
add_action('wiz_ajax_update_user_avatar', 'handle_update_user_avatar');

/**
 * Handle user avatar deletion
 */
function handle_delete_user_avatar() {
    if (!current_user_can('edit_user', $_POST['user_id'])) {
        wp_send_json_error('Permission denied');
    }

    $avatar = new WizardAvatar($_POST['user_id']);
    $result = $avatar->delete_avatar();
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'message' => 'Avatar removed successfully',
        'avatar_url' => $avatar->get_avatar_url(96),
        'avatar_html' => $avatar->get_avatar(96)
    ]);
}
add_action('wiz_ajax_delete_user_avatar', 'handle_delete_user_avatar');

/**
 * Handle team avatar upload
 */
function handle_update_team_avatar() {
    $team_id = isset($_POST['team_id']) ? absint($_POST['team_id']) : 0;
    if (!$team_id) {
        wp_send_json_error('Invalid team ID');
    }

    $teams = new WizardTeams();
    if (!$teams->is_team_admin($team_id, get_current_user_id())) {
        wp_send_json_error('Permission denied');
    }

    if (empty($_FILES['team_avatar'])) {
        wp_send_json_error('No file uploaded');
    }

    $result = $teams->update_team_avatar($team_id, $_FILES['team_avatar']);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'message' => 'Team avatar updated successfully',
        'avatar_html' => $teams->get_team_avatar($team_id, 64)
    ]);
}
add_action('wiz_ajax_update_team_avatar', 'handle_update_team_avatar');

/**
 * Handle team avatar deletion
 */
function handle_delete_team_avatar() {
    $team_id = isset($_POST['team_id']) ? absint($_POST['team_id']) : 0;
    if (!$team_id) {
        wp_send_json_error('Invalid team ID');
    }

    $teams = new WizardTeams();
    if (!$teams->is_team_admin($team_id, get_current_user_id())) {
        wp_send_json_error('Permission denied');
    }

    $result = $teams->delete_team_avatar($team_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'message' => 'Team avatar removed successfully',
        'avatar_html' => $teams->get_team_avatar($team_id, 64)
    ]);
}
add_action('wiz_ajax_delete_team_avatar', 'handle_delete_team_avatar'); 