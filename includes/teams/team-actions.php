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