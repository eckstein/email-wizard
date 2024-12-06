<?php

/**
 * Template Name: Login Page
 */

get_header();

// Get any error messages
$error = isset($_GET['login']) ? $_GET['login'] : '';
$redirect = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url();

$args = array(
    'redirect' => $redirect,
    'form_id' => 'loginform',
    'label_username' => 'Email',
    'label_password' => 'Password',
    'label_remember' => 'Remember Me',
    'label_log_in' => 'Log In',
    'remember' => false,
    'value_remember' => true
);
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="login-page">
            <div class="wizard-form-content">
                <div class="login-header-container">
                    <?php
                    $site_logo = get_theme_mod('emailwizard_site_logo') ?? '';
                    ?>
                    <?php if ($site_logo): ?>
                        <img src="<?php echo esc_url($site_logo); ?>" alt="Email Wizard Logo" class="login-logo">
                    <?php endif; ?>

                    <h3 class="wizard-form-section-title">Login to Your Account</h3>
                </div>

                <?php if ($error === 'failed'): ?>
                    <div class="wizard-message error">
                        <p>Invalid username or password. Please try again.</p>
                    </div>
                <?php endif; ?>

                <?php wp_login_form($args); ?>

                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="lost-password-link">Lost your password?</a>
            </div>
        </div>
    </main>
</div>

<?php get_footer(); ?>