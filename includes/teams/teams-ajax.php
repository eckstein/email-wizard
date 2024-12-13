<?php
add_action('wiz_ajax_add_wizard_team', 'add_wizard_team_ajax');
function add_wizard_team_ajax()
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not authorized']);
    }

    // For new team creation, use current active team's nonce
    $teamManager = new WizardTeams();
    $currentTeam = $teamManager->get_active_team($user_id);
    
    if (!check_ajax_referer('wizard_security_'.$currentTeam, 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token. Active team may have been switched in another tab.']);
    }

    $team_data = array(
        'name' => sanitize_text_field($_POST['team_name']),
        'created_by' => $user_id,
    );

    $newTeamId = $teamManager->create_team($team_data);
    if ($newTeamId) {
        // add user to team as admin
        $teamManager->add_team_member($newTeamId, $user_id, 'admin');
        // Make active team
        $teamManager->switch_active_team($user_id, $newTeamId);

        wp_send_json_success(['team_id' => $newTeamId]);
    } else {
        wp_send_json_error(['message' => 'Failed to create team']);
    }
}

add_action('wiz_ajax_switch_wizard_team', 'switch_wizard_team_ajax');
function switch_wizard_team_ajax() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not authorized']);
    }

    $team_id = sanitize_text_field($_POST['team_id']);
    if (empty($team_id)) {
        wp_send_json_error(['message' => 'Team ID is required']);
    }

    // When switching teams, verify against current active team
    $teamManager = new WizardTeams();
    $currentTeam = $teamManager->get_active_team($user_id);
    
    if (!check_ajax_referer('wizard_security_'.$currentTeam, 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token. Active team may have been switched in another tab.']);
    }

    $result = $teamManager->switch_active_team($user_id, $team_id);
    if ($result) {
        wp_send_json_success([
            'message' => 'Team switched successfully',
            'new_nonce' => wp_create_nonce('wizard_security_'.$team_id) // Provide new nonce for the new team
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to switch team']);
    }
}

add_action('wiz_ajax_update_team_settings', 'handle_team_settings_update_ajax');
function handle_team_settings_update_ajax() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not authorized']);
    }

    $team_id = sanitize_text_field($_POST['team_id']);
    if (empty($team_id)) {
        wp_send_json_error(['message' => 'Team ID is required']);
    }

    // Verify nonce with the team being updated
    if (!check_ajax_referer('wizard_security_'.$team_id, 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token. Active team may have been switched in another tab.']);
    }

    $teamManager = new WizardTeams();
    
    // Verify user is team admin
    if (!$teamManager->is_team_admin($team_id, $user_id)) {
        wp_send_json_error(['message' => 'You do not have permission to update team settings']);
    }

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

    // Handle member role updates if present
    $roles_updated = false;
    if (isset($_POST['roles_updated']) && isset($_POST['member_role']) && is_array($_POST['member_role'])) {
        foreach ($_POST['member_role'] as $member_id => $new_role) {
            // Skip if role is not valid
            if (!in_array($new_role, ['member', 'admin'])) {
                continue;
            }

            // Skip updating the team owner's role
            if ($member_id == $user_id) {
                continue;
            }

            $update_result = $teamManager->update_member_role($team_id, $member_id, $new_role);
            if (is_wp_error($update_result)) {
                wp_send_json_error(['message' => $update_result->get_error_message()]);
            }
        }
    }

    wp_send_json_success([
        'message' => 'Team settings updated successfully'
    ]);
}

add_action('wiz_ajax_get_team_settings', 'handle_get_team_settings_ajax');
function handle_get_team_settings_ajax() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not authorized']);
    }

    $team_id = sanitize_text_field($_POST['team_id']);
    if (empty($team_id)) {
        wp_send_json_error(['message' => 'Team ID is required']);
    }

    // Verify nonce with the team ID
    if (!check_ajax_referer('wizard_security_'.$team_id, 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token. Active team may have been switched in another tab.']);
    }

    $teamManager = new WizardTeams();
    
    // Verify user is team admin
    if (!$teamManager->is_team_admin($team_id, $user_id)) {
        wp_send_json_error(['message' => 'You do not have permission to manage team settings']);
    }

    // Get team data
    $team = $teamManager->get_team($team_id);
    if (!$team) {
        wp_send_json_error(['message' => 'Team not found']);
    }

    // Set up template args
    $args = [
        'team' => $team,
        'teamsManager' => $teamManager
    ];

    // Load and return the modal template
    ob_start();
    include(plugin_dir_path(dirname(dirname(__FILE__))) . 'public/templates/account/partials/team-settings-modal.php');
    $modal_content = ob_get_clean();

    if (empty($modal_content)) {
        wp_send_json_error(['message' => 'Failed to load team settings template']);
    }

    wp_send_json_success(['html' => $modal_content]);
}