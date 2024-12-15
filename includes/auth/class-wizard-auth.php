<?php

class WizardAuth {
    private static $protected_post_types = ['wiz_template'];
    private static $protected_pages = [];
    private $login_page_id;
    private $register_page_id;

    public function __construct() {
        add_action('init', [$this, 'create_auth_pages']);
        add_action('template_redirect', [$this, 'check_auth_requirements']);
        add_filter('wizard_protected_pages', [$this, 'register_protected_pages']);
        
        // Add template loading filter
        add_filter('template_include', [$this, 'load_auth_template']);
        
        // Add styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_auth_styles']);
    }

    public function enqueue_auth_styles() {
        if (is_page([$this->login_page_id, $this->register_page_id])) {
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
        }
        return $template;
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