<?php
// This template is included by page-register.php
// Variables $invite_email and $token are available from the parent template
?>

<div class="wizard-auth-container">
    <div class="wizard-auth-box">
        <h2><?php _e('Accept Team Invitation', 'wizard'); ?></h2>
        
        <p class="invite-message">
            <?php printf(__('You\'ve been invited to join a team. Create your account to accept.', 'wizard')); ?>
        </p>

        <form name="registerform" id="registerform" action="<?php echo esc_url(site_url('wp-login.php?action=register', 'login_post')); ?>" method="post">
            <?php wp_nonce_field('wordpress-register', '_wpnonce', true); ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_url(add_query_arg(['action' => 'accept_team_invite', 'token' => $token], home_url())); ?>" />
            <input type="hidden" name="invite_token" value="<?php echo esc_attr($token); ?>" />
            
            <div class="form-field">
                <label><?php _e('Email Address', 'wizard'); ?></label>
                <div class="readonly-field"><?php echo esc_html($invite_email); ?></div>
                <input type="hidden" name="user_email" value="<?php echo esc_attr($invite_email); ?>" />
                <input type="hidden" name="user_login" value="<?php echo esc_attr($invite_email); ?>" />
            </div>
            
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php esc_attr_e('Create Account', 'wizard'); ?>" />
            </p>
        </form>
        
        <p class="login-link">
            <?php printf(
                __('Already have an account? %s', 'wizard'),
                '<a href="' . esc_url(wp_login_url(add_query_arg(['action' => 'accept_team_invite', 'token' => $token], home_url()))) . '">' . __('Log in', 'wizard') . '</a>'
            ); ?>
        </p>
    </div>
</div> 