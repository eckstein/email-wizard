<?php

class WizardAuth {
    private static $protected_post_types = ['wiz_template'];
    private static $protected_pages = [];
    private $login_page_id;

    public function __construct() {
        add_action('init', [$this, 'create_login_page']);
        add_action('template_redirect', [$this, 'check_auth_requirements']);
        add_filter('wizard_protected_pages', [$this, 'register_protected_pages']);
        add_action('wp_login', [$this, 'handle_login_redirect'], 10, 2);
        
        // Override WordPress login page
        add_action('init', [$this, 'override_login_page']);
        add_filter('login_url', [$this, 'get_login_page_url'], 10, 3);
        
        // Add template loading filter
        add_filter('template_include', [$this, 'load_login_template']);
        
        // Add login styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_login_styles']);
    }

    public function enqueue_login_styles() {
        if (is_page($this->login_page_id)) {
            wp_enqueue_style(
                'wizard-login-style', 
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/components/_login.css',
                array(),
                EMAILWIZARD_VERSION
            );
        }
    }

    public function create_login_page() {
        // Check if login page already exists
        $existing_page = get_page_by_path('login');
        if ($existing_page) {
            $this->login_page_id = $existing_page->ID;
            return;
        }

        // Create the login page
        $page_data = array(
            'post_title'    => 'Login',
            'post_name'     => 'login',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'post_author'   => 1
        );

        $this->login_page_id = wp_insert_post($page_data);
    }

    public function load_login_template($template) {
        if (is_page($this->login_page_id)) {
            $new_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/templates/auth/page-login.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    public function override_login_page() {
        global $pagenow;
        
        // Allow access to wp-login.php for actual login processing and other actions
        $allowed_actions = ['logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register'];
        if ($pagenow === 'wp-login.php') {
            // If it's a POST request or has an allowed action, let WordPress handle it
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $allowed_actions))) {
                return;
            }
            
            // For regular login page visits, redirect to our custom page
            if (!isset($_REQUEST['action'])) {
                wp_safe_redirect($this->get_login_page_url());
                exit();
            }
        }
    }

    public function get_login_page_url($url = '', $redirect = '', $force_reauth = false) {
        $login_url = get_permalink($this->login_page_id);
        
        if (!empty($redirect)) {
            $login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
        }
        
        if ($force_reauth) {
            $login_url = add_query_arg('reauth', '1', $login_url);
        }
        
        return $login_url;
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
            $redirect_url = $this->get_login_page_url('', get_permalink());
            wp_safe_redirect($redirect_url);
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

    public function handle_login_redirect($user_login, $user) {
        if (isset($_GET['redirect_to'])) {
            wp_safe_redirect($_GET['redirect_to']);
            exit;
        }
    }
} 