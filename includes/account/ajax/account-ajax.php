<?php
/**
 * AJAX handlers for account management
 */

add_action('wiz_ajax_update_account', 'handle_account_update_ajax');
function handle_account_update_ajax() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not authorized']);
    }

    // Verify nonce
    if (!check_ajax_referer('wizard_update_account', 'wizard_update_account_nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }

    $updated = false;
    $errors = [];

    // Process standard fields
    $fields = [
        'first_name',
        'last_name',
        'display_name',
        'user_email'
    ];

    $user_data = ['ID' => $user_id];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $user_data[$field] = sanitize_text_field($_POST[$field]);
        }
    }

    // Handle password update
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        $user = get_user_by('id', $user_id);
        if (!wp_check_password($_POST['current_password'], $user->data->user_pass, $user_id)) {
            wp_send_json_error(['message' => 'Current password is incorrect']);
        }

        if (strlen($_POST['new_password']) < 8) {
            wp_send_json_error(['message' => 'Password must be at least 8 characters long']);
        }

        $user_data['user_pass'] = $_POST['new_password'];
    }

    // Update user data
    if (count($user_data) > 1) { // More than just ID
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        $updated = true;
    }

    if ($updated) {
        wp_send_json_success([
            'message' => 'Account information updated successfully'
        ]);
    } else {
        wp_send_json_error(['message' => 'No changes were made']);
    }
}

add_action('wiz_ajax_update_avatar', 'handle_avatar_upload_ajax');
function handle_avatar_upload_ajax() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not authorized']);
    }

    if (empty($_FILES['file'])) {
        wp_send_json_error(['message' => 'No file uploaded']);
    }

    $avatar_handler = new WizardAvatar($user_id);
    $avatar_result = $avatar_handler->handle_upload($_FILES['file']);

    if (is_wp_error($avatar_result)) {
        wp_send_json_error(['message' => $avatar_result->get_error_message()]);
    }

    $set_result = $avatar_handler->set_avatar($avatar_result);
    if (is_wp_error($set_result)) {
        wp_delete_attachment($avatar_result, true);
        wp_send_json_error(['message' => $set_result->get_error_message()]);
    }

    wp_send_json_success([
        'message' => 'Avatar uploaded successfully',
        'avatar_url' => wp_get_attachment_url($avatar_result)
    ]);
}

add_action('wiz_ajax_delete_avatar', 'handle_avatar_delete_ajax');
function handle_avatar_delete_ajax() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not authorized']);
    }

    $avatar_handler = new WizardAvatar($user_id);
    $delete_result = $avatar_handler->delete_avatar();

    if (is_wp_error($delete_result)) {
        wp_send_json_error(['message' => $delete_result->get_error_message()]);
    }

    wp_send_json_success([
        'message' => 'Avatar deleted successfully',
        'default_avatar' => get_avatar_url($user_id)
    ]);
} 