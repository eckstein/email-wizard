<?php

/**
 * Template Name: Login Page
 */

get_header();

$error = isset($_GET['login']) ? $_GET['login'] : '';
$redirect = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url();
?>

<div class="wizard-auth-container">
    <div class="wizard-auth-box">
        <h2><?php _e('Log In', 'wizard'); ?></h2>

        <?php if ($error === 'failed'): ?>
            <div class="message-box error">
                <?php _e('Invalid email or password. Please try again.', 'wizard'); ?>
            </div>
        <?php endif; ?>

        <form name="loginform" id="loginform" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
            <p>
                <label for="user_login"><?php _e('Email Address', 'wizard'); ?></label>
                <input type="text" name="log" id="user_login" class="input" required />
            </p>

            <p>
                <label for="user_pass"><?php _e('Password', 'wizard'); ?></label>
                <input type="password" name="pwd" id="user_pass" class="input" required />
            </p>

            <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect); ?>" />

            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php esc_attr_e('Log In', 'wizard'); ?>" />
            </p>
        </form>

        <p class="login-link">
            <?php printf(
                __('Forgot your password? %s', 'wizard'),
                '<a href="' . esc_url(home_url('/reset-password/')) . '">' . __('Reset it here', 'wizard') . '</a>'
            ); ?>
        </p>
    </div>
</div>

<?php get_footer(); ?>