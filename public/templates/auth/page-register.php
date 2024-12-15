<?php
get_header();

$invite_email = isset($_GET['invite_email']) ? rawurldecode($_GET['invite_email']) : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

// If this is an invite, use the invite template instead
if ($invite_email && $token) {
    include plugin_dir_path(__FILE__) . 'page-accept-invite.php';
    return;
}
?>

<div class="wizard-auth-container">
    <div class="wizard-auth-box">
        <h2><?php _e('Create Account', 'wizard'); ?></h2>

        <form name="registerform" id="registerform" action="<?php echo esc_url(site_url('wp-login.php?action=register', 'login_post')); ?>" method="post">
            <?php wp_nonce_field('wordpress-register', '_wpnonce', true); ?>
            
            <p>
                <label for="user_email"><?php _e('Email Address', 'wizard'); ?></label>
                <input type="email" name="user_email" id="user_email" class="input" required />
                <input type="hidden" name="user_login" id="user_login" />
            </p>
            
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php esc_attr_e('Register', 'wizard'); ?>" />
            </p>
        </form>
        
        <p class="login-link">
            <?php printf(
                __('Already have an account? %s', 'wizard'),
                '<a href="' . esc_url(home_url('/login/')) . '">' . __('Log in', 'wizard') . '</a>'
            ); ?>
        </p>
    </div>
</div>

<script>
document.getElementById('registerform').addEventListener('submit', function(e) {
    // Set username to email address
    var emailField = document.getElementById('user_email');
    var usernameField = document.getElementById('user_login');
    usernameField.value = emailField.value;
});
</script>

<?php get_footer(); ?> 