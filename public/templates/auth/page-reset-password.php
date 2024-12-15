<?php
get_header();

$action = isset($_GET['action']) ? $_GET['action'] : 'lostpassword';
$errors = [];
$success = false;

// Handle different reset password stages
if ($action === 'rp') {
    $login = isset($_GET['login']) ? sanitize_user($_GET['login']) : '';
    $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
    
    // Verify key
    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        $errors[] = __('This password reset link has expired or is invalid.', 'wizard');
        $action = 'error';
    }
}
?>

<div class="wizard-auth-container">
    <div class="wizard-auth-box">
        <?php if ($action === 'lostpassword'): ?>
            <h2><?php _e('Reset Password', 'wizard'); ?></h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="message-box success">
                    <?php _e('Password reset email has been sent. Please check your inbox.', 'wizard'); ?>
                </div>
            <?php endif; ?>

            <form name="lostpasswordform" id="lostpasswordform" action="<?php echo esc_url(wp_lostpassword_url()); ?>" method="post">
                <p>
                    <label for="user_login"><?php _e('Email Address', 'wizard'); ?></label>
                    <input type="email" name="user_login" id="user_login" class="input" required />
                </p>
                
                <?php do_action('lostpassword_form'); ?>
                
                <p class="submit">
                    <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php esc_attr_e('Get New Password', 'wizard'); ?>" />
                </p>
            </form>

        <?php elseif ($action === 'rp'): ?>
            <h2><?php _e('Set New Password', 'wizard'); ?></h2>

            <form name="resetpassform" id="resetpassform" action="<?php echo esc_url(site_url('wp-login.php?action=resetpass')); ?>" method="post" autocomplete="off">
                <input type="hidden" name="rp_key" value="<?php echo esc_attr($key); ?>" />
                <input type="hidden" name="rp_login" value="<?php echo esc_attr($login); ?>" />

                <p>
                    <label for="pass1"><?php _e('New Password', 'wizard'); ?></label>
                    <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" required />
                </p>

                <p>
                    <label for="pass2"><?php _e('Confirm Password', 'wizard'); ?></label>
                    <input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" required />
                </p>

                <div class="pass-strength-result" id="pass-strength-result"></div>

                <?php do_action('resetpass_form', $user); ?>

                <p class="submit">
                    <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php esc_attr_e('Reset Password', 'wizard'); ?>" />
                </p>
            </form>

        <?php else: ?>
            <h2><?php _e('Error', 'wizard'); ?></h2>
            
            <div class="message-box error">
                <?php 
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        echo '<p>' . esc_html($error) . '</p>';
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <p class="login-link">
            <?php printf(
                __('Remember your password? %s', 'wizard'),
                '<a href="' . esc_url(wp_login_url()) . '">' . __('Log in', 'wizard') . '</a>'
            ); ?>
        </p>
    </div>
</div>

<?php get_footer(); ?> 