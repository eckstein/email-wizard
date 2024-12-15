<?php
// This template is included by page-register.php
// Variables $invite_email and $token are available from the parent template

$message = '';
$message_type = 'success';

if (empty($invite_email) || empty($token)) {
    $message = __('Invalid invitation link.', 'wizard');
    $message_type = 'error';
} else {
    // Verify the invite is valid
    $invite = wizard_validate_invite($token);
    if (!$invite) {
        $message = __('This invitation has expired or is no longer valid.', 'wizard');
        $message_type = 'error';
    } else {
        // Check if user already exists
        $existing_user = get_user_by('email', $invite_email);
        if ($existing_user) {
            wp_redirect(wp_login_url(add_query_arg([
                'action' => 'accept_team_invite',
                'token' => $token
            ], home_url())));
            exit;
        }
        
        // Process the registration
        if (!username_exists($invite_email) && !email_exists($invite_email)) {
            // Generate a random password (user will set their own via email)
            $random_password = wp_generate_password(24, true);
            
            // Create the user
            $user_id = wp_create_user($invite_email, $random_password, $invite_email);
            
            if (!is_wp_error($user_id)) {
                // Trigger the invite acceptance
                $teams = new WizardTeams();
                $teams->accept_team_invite($token, $user_id);
                
                // Send password setup email
                wp_new_user_notification($user_id, null, 'user');
                
                $message = __('Your account has been created successfully!', 'wizard');
                $show_email_message = true;
            } else {
                $message = __('There was an error creating your account. Please try again.', 'wizard');
                $message_type = 'error';
            }
        }
    }
}
?>

<div class="wizard-auth-container">
    <div class="wizard-auth-box">
        <h2><?php _e('Accept Team Invitation', 'wizard'); ?></h2>
        
        <div class="message-box <?php echo esc_attr($message_type); ?>">
            <?php echo esc_html($message); ?>
            <?php if (isset($show_email_message) && $show_email_message): ?>
                <p class="sub-message"><?php _e('Please check your email for instructions to set your password.', 'wizard'); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($message_type === 'error'): ?>
            <p class="login-link">
                <a href="<?php echo esc_url(home_url()); ?>"><?php _e('Return to Homepage', 'wizard'); ?></a>
            </p>
        <?php endif; ?>
    </div>
</div> 