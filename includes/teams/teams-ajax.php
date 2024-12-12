<?php
add_action('wiz_ajax_add_wizard_team', 'add_wizard_team_ajax');
function add_wizard_team_ajax()
{
    $user_id = sanitize_text_field($_POST['user_id']);
    if (empty($user_id)) {
        wp_send_json_error(array('message' => 'Invalid team or user ID.'));
    }
    $team_data = array(
        'name' => sanitize_text_field($_POST['team_name']),
        'created_by' => sanitize_text_field($_POST['user_id']),
    );
    $teamManager = new WizardTeams();
    $newTeamId = $teamManager->create_team($team_data);
    if ($newTeamId) {
        // add user to team as admin
        $teamManager->add_team_member($newTeamId, $user_id, 'admin');
        // Make active team
        $teamManager->switch_active_team($user_id, $newTeamId);

        wp_send_json_success(array('team_id' => $newTeamId));
    } else {
        wp_send_json_error(array('message' => 'Failed to create team.'));
    }
}

add_action('wiz_ajax_switch_wizard_team', 'switch_wizard_team_ajax');
function switch_wizard_team_ajax() {
    $user_id = sanitize_text_field($_POST['user_id']);
    $team_id = sanitize_text_field($_POST['team_id']);
    if (empty($user_id) || empty($team_id)) {
        wp_send_json_error(array('message' => 'Invalid team or user ID.'));
    }
    $teamManager = new WizardTeams();
    $result = $teamManager->switch_active_team($user_id, $team_id);
    if ($result) {
        wp_send_json_success(array('message' => 'Team switched successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to switch team.'));
    }
}

add_action('wiz_ajax_update_team_settings', 'handle_team_settings_update_ajax');
function handle_team_settings_update_ajax() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not authorized']);
    }

    // Verify nonce
    if (!check_ajax_referer('wizard_update_team_settings', 'wizard_update_team_settings_nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }

    $team_id = sanitize_text_field($_POST['team_id']);
    if (empty($team_id)) {
        wp_send_json_error(['message' => 'Team ID is required']);
    }

    $teamManager = new WizardTeams();
    
    // Verify user is team admin
    if (!$teamManager->is_team_admin($team_id, $user_id)) {
        wp_send_json_error(['message' => 'You do not have permission to update team settings']);
    }

    $team_data = [
        'id' => $team_id,
        'name' => sanitize_text_field($_POST['team_name']),
        'description' => sanitize_textarea_field($_POST['team_description'])
    ];

    // Handle avatar upload if present
    if (!empty($_FILES['team_avatar']['name'])) {
        $avatar = new WizardAvatar();
        $avatar_result = $avatar->handle_upload($_FILES['team_avatar']);

        if (is_wp_error($avatar_result)) {
            wp_send_json_error(['message' => $avatar_result->get_error_message()]);
        }

        // Set the avatar for the team
        $set_result = $teamManager->set_team_avatar($team_id, $avatar_result);
        if (is_wp_error($set_result)) {
            wp_delete_attachment($avatar_result, true);
            wp_send_json_error(['message' => $set_result->get_error_message()]);
        }
    }

    // Handle avatar deletion
    if (isset($_POST['delete_team_avatar'])) {
        $delete_result = $teamManager->delete_team_avatar($team_id);
        if (is_wp_error($delete_result)) {
            wp_send_json_error(['message' => $delete_result->get_error_message()]);
        }
    }

    // Update team data
    $result = $teamManager->update_team($team_id, [
        'name' => sanitize_text_field($_POST['team_name']),
        'description' => sanitize_textarea_field($_POST['team_description'])
    ]);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success([
        'message' => 'Team settings updated successfully'
    ]);
}