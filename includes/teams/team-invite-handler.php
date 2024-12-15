<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allow + character in usernames for email addresses
 */
function wizard_allow_email_usernames($username, $raw_username, $strict) {
    // Only modify if it looks like an email address
    if (strpos($raw_username, '@') !== false) {
        // Add back the + character that WordPress removed
        // First get the local part of the email (before @)
        $parts = explode('@', $raw_username);
        $local = $parts[0];
        $domain = $parts[1];
        
        // Reconstruct the username/email with + preserved
        if (strpos($local, '+') !== false) {
            $username = $local . '@' . $domain;
        }
    }
    return $username;
}
add_filter('sanitize_user', 'wizard_allow_email_usernames', 10, 3);

/**
 * Modify WordPress's username sanitization rules to allow + in usernames
 */
function wizard_modify_username_rules($username_rules) {
    // Only modify if we're processing a registration
    if (!empty($_POST['invite_token'])) {
        // Add + to the allowed characters
        return $username_rules . '+';
    }
    return $username_rules;
}
add_filter('sanitize_user_regexp', 'wizard_modify_username_rules', 10, 1);

/**
 * Validate team invitation token
 */
function wizard_validate_invite($token) {
    global $wpdb;
    
    $invite = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}team_invites 
        WHERE token = %s 
        AND status = 'pending'
        AND expires_at > NOW()",
        $token
    ));

    return $invite;
}

/**
 * Handle team invitation links
 */
function wizard_handle_team_invite() {
    if (!isset($_GET['action']) || $_GET['action'] !== 'accept_team_invite' || !isset($_GET['token'])) {
        return;
    }

    $token = sanitize_text_field($_GET['token']);
    $invite = wizard_validate_invite($token);

    // For registration and login redirects, we'll validate the token again later
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
                'invite_email' => rawurlencode($invite->email),
                'token' => $token
            ], home_url('/register/')));
            exit;
        }
    }

    // For actual invite acceptance, validate strictly
    if (!$invite) {
        wp_die(__('This invitation is invalid or has expired.', 'wizard'));
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
    
    // Validate the invite
    $invite = wizard_validate_invite($token);
    if (!$invite) {
        return;
    }

    // Verify the email matches
    $user = get_user_by('ID', $user_id);
    if ($user->user_email !== $invite->email) {
        return;
    }

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