<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle team invitation links
 */
function wizard_handle_team_invite() {
    if (!isset($_GET['action']) || $_GET['action'] !== 'accept_team_invite' || !isset($_GET['token'])) {
        return;
    }

    $token = sanitize_text_field($_GET['token']);
    
    // Get the invitation
    global $wpdb;
    $invite = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}team_invites 
        WHERE token = %s 
        AND status = 'pending'
        AND expires_at > NOW()",
        $token
    ));

    if (!$invite) {
        wp_die(__('This invitation is invalid or has expired.', 'wizard'));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        // Check if a user with this email exists
        $existing_user = get_user_by('email', $invite->email);
        
        if ($existing_user) {
            // Redirect to WordPress login with return URL
            wp_redirect(wp_login_url(add_query_arg([
                'action' => 'accept_team_invite',
                'token' => $token
            ], home_url())));
            exit;
        } else {
            // Redirect to our registration page
            wp_redirect(add_query_arg([
                'invite_email' => urlencode($invite->email),
                'token' => $token
            ], home_url('/register/')));
            exit;
        }
    }

    // User is logged in, verify email matches
    $current_user = wp_get_current_user();
    if ($current_user->user_email !== $invite->email) {
        wp_die(__('This invitation was sent to a different email address.', 'wizard'));
    }

    // Accept the invitation
    $teams = new WizardTeams();
    $result = $teams->accept_team_invite($token, $current_user->ID);

    if (is_wp_error($result)) {
        wp_die($result->get_error_message());
    }

    // Redirect to the team dashboard
    wp_redirect(home_url('/dashboard/'));
    exit;
}
add_action('init', 'wizard_handle_team_invite');

/**
 * Handle team invitation after user registration
 */
function wizard_handle_invite_after_registration($user_id) {
    // Check if this registration came from an invite
    if (empty($_POST['invite_token'])) {
        return;
    }

    $token = sanitize_text_field($_POST['invite_token']);
    $teams = new WizardTeams();
    $teams->accept_team_invite($token, $user_id);
}
add_action('user_register', 'wizard_handle_invite_after_registration');

/**
 * Prevent email field changes on registration if coming from invitation
 */
function wizard_lock_invite_email() {
    if (!empty($_GET['invite_email'])) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var emailField = document.getElementById('user_email');
            if (emailField) {
                emailField.readOnly = true;
                emailField.style.backgroundColor = '#f0f0f0';
            }
        });
        </script>
        <?php
    }
}
add_action('login_enqueue_scripts', 'wizard_lock_invite_email'); 