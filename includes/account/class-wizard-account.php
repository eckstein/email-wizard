<?php
/**
 * Account management interface with tabbed navigation.
 * 
 * Extends the WizardTabs base class to provide account-specific functionality
 * including form handling, user data management, and account settings templates.
 * 
 * @since 1.0.0
 */

// Require the WizardTabs interface
require_once plugin_dir_path(dirname(__FILE__)) . 'interface/class-wizard-tabs.php';

class WizardAccount extends WizardTabs {
    /** @var WP_User Current WordPress user object */
    private $user;

    /**
     * Initialize the account interface.
     * 
     * Sets up tabs, handlers, and user data if authorized.
     */
    public function __construct() {
        parent::__construct();
        
        if ($this->is_authorized) {
            $this->user = wp_get_current_user();
            $this->register_default_tabs();
        }
    }

    /**
     * Get the container ID for the account tabs interface.
     * 
     * @return string Container ID
     */
    protected function get_container_id() {
        return 'wizard-account-tabs';
    }

    /**
     * Get the template base directory for account templates.
     * 
     * @return string Template base directory name
     */
    protected function get_template_base() {
        return 'account';
    }

    /**
     * Initialize the account interface.
     * 
     * Handles form submissions and message display.
     */
    public function init() {
        // Start session if needed for messages
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Register the default account management tabs.
     */
    private function register_default_tabs() {
        $this->add_tab([
            'id' => 'account',
            'title' => 'Account Settings',
            'icon' => 'fa-solid fa-user'
        ]);

        $this->add_tab([
            'id' => 'teams',
            'title' => 'Teams',
            'icon' => 'fa-solid fa-users'
        ]);

        $this->add_tab([
            'id' => 'plan',
            'title' => 'Manage Plan',
            'icon' => 'fa-solid fa-sliders'
        ]);

        $this->add_tab([
            'id' => 'billing',
            'title' => 'Billing Settings',
            'icon' => 'fa-solid fa-credit-card'
        ]);
    }

    /**
     * Add a message to be displayed to the user.
     * 
     * Handles both immediate display and redirect scenarios using sessions.
     * 
     * @param string $type The message type ('error', 'success', etc.)
     * @param string $text The message text to display
     */
    private function add_message($type, $text) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['wizard_messages'])) {
            $_SESSION['wizard_messages'] = array();
        }
        
        $_SESSION['wizard_messages'][] = array(
            'type' => $type,
            'text' => $text
        );
    }
}
