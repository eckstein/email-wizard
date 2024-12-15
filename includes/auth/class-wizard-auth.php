<?php

class WizardAuth {
    private static $protected_post_types = ['wiz_template'];
    private static $protected_pages = [];
    private $login_page_id;
    private $register_page_id;
    private $reset_page_id;

    public function __construct() {
        add_action('init', [$this, 'create_auth_pages']);
        add_action('template_redirect', [$this, 'check_auth_requirements']);
        add_filter('wizard_protected_pages', [$this, 'register_protected_pages']);
        
        // Handle WordPress login/register pages
        add_action('init', [$this, 'handle_auth_redirects']);
        add_filter('login_url', [$this, 'get_login_url'], 10, 3);
        add_filter('register_url', [$this, 'get_register_url']);
        add_filter('lostpassword_url', [$this, 'get_lostpassword_url'], 10, 2);
        
        // Add template loading filter
        add_filter('template_include', [$this, 'load_auth_template']);
        
        // Add styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_auth_styles']);

        // Handle auth errors
        add_action('wp_login_failed', [$this, 'handle_login_failed']);
        add_filter('registration_errors', [$this, 'handle_registration_errors'], 10, 3);
        add_action('retrieve_password_message', [$this, 'customize_password_reset_message'], 10, 4);
    }

    public function enqueue_auth_styles() {
        if (is_page([$this->login_page_id, $this->register_page_id, $this->reset_page_id])) {
            wp_enqueue_style(
                'wizard-auth-style', 
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/components/_login.css',
                array(),
                EMAILWIZARD_VERSION
            );
        }
    }

    public function create_auth_pages() {
        // Create login page if it doesn't exist
        $login_page = get_page_by_path('login');
        if (!$login_page) {
            $this->login_page_id = wp_insert_post([
                'post_title'    => 'Login',
                'post_name'     => 'login',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_content'  => '',
                'post_author'   => 1
            ]);
        } else {
            $this->login_page_id = $login_page->ID;
        }

        // Create register page if it doesn't exist
        $register_page = get_page_by_path('register');
        if (!$register_page) {
            $this->register_page_id = wp_insert_post([
                'post_title'    => 'Register',
                'post_name'     => 'register',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_content'  => '',
                'post_author'   => 1
            ]);
        } else {
            $this->register_page_id = $register_page->ID;
        }

        // Create reset password page if it doesn't exist
        $reset_page = get_page_by_path('reset-password');
        if (!$reset_page) {
            $this->reset_page_id = wp_insert_post([
                'post_title'    => 'Reset Password',
                'post_name'     => 'reset-password',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_content'  => '',
                'post_author'   => 1
            ]);
        } else {
            $this->reset_page_id = $reset_page->ID;
        }
    }

    public function handle_auth_redirects() {
        global $pagenow;
        
        if ($pagenow === 'wp-login.php') {
            $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
            
            // Allow form processing
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                return;
            }

            // Handle different auth actions
            switch ($action) {
                case 'register':
                    wp_redirect(home_url('/register/'));
                    exit;
                
                case 'lostpassword':
                case 'rp':
                case 'resetpass':
                    wp_redirect(home_url('/reset-password/'));
                    exit;

                case 'logout':
                    // Let WordPress handle logout
                    return;

                default:
                    if (empty($action)) {
                        wp_redirect(home_url('/login/'));
                        exit;
                    }
            }
        }
    }

    public function load_auth_template($template) {
        if (is_page($this->login_page_id)) {
            $new_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/templates/auth/page-login.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        } elseif (is_page($this->register_page_id)) {
            $new_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/templates/auth/page-register.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        } elseif (is_page($this->reset_page_id)) {
            $new_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/templates/auth/page-reset-password.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    public function get_login_url($url, $redirect = '', $force_reauth = false) {
        $login_url = home_url('/login/');
        
        if (!empty($redirect)) {
            $login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
        }
        
        if ($force_reauth) {
            $login_url = add_query_arg('reauth', '1', $login_url);
        }
        
        return $login_url;
    }

    public function get_register_url($url) {
        return home_url('/register/');
    }

    public function get_lostpassword_url($url, $redirect = '') {
        $lost_password_url = home_url('/reset-password/');
        
        if (!empty($redirect)) {
            $lost_password_url = add_query_arg('redirect_to', urlencode($redirect), $lost_password_url);
        }
        
        return $lost_password_url;
    }

    public function handle_login_failed($username) {
        $referrer = wp_get_referer();
        if ($referrer && strpos($referrer, 'login') !== false) {
            wp_redirect(add_query_arg('login', 'failed', home_url('/login/')));
            exit;
        }
    }

    public function handle_registration_errors($errors, $sanitized_user_login, $user_email) {
        if ($errors->has_errors()) {
            $_SESSION['registration_errors'] = $errors->get_error_messages();
            wp_redirect(home_url('/register/'));
            exit;
        }
        return $errors;
    }

    public function customize_password_reset_message($message, $key, $user_login, $user_data) {
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $reset_url = home_url("/reset-password/?action=rp&key=$key&login=" . rawurlencode($user_login));

        $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
        $message .= sprintf(__('Site Name: %s'), $site_name) . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, ignore this email and nothing will happen.') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= $reset_url . "\r\n";

        return $message;
    }

    public function register_protected_pages($pages) {
        self::$protected_pages = array_merge(self::$protected_pages, $pages);
        return self::$protected_pages;
    }

    public function check_auth_requirements() {
        // Skip check for admin users
        if (current_user_can('administrator')) {
            return;
        }

        // Check if current page requires authentication
        if ($this->requires_auth() && !is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }
    }

    private function requires_auth() {
        // Check for protected post types
        if (is_singular(self::$protected_post_types)) {
            return true;
        }

        // Check for protected pages
        if (is_page(self::$protected_pages)) {
            return true;
        }

        // Check for archive pages of protected post types
        if (is_post_type_archive(self::$protected_post_types)) {
            return true;
        }

        return false;
    }
} 