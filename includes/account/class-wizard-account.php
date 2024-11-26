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
            $this->register_default_handlers();
        }
    }

    /**
     * Get the container ID for the account tabs interface.
     * 
     * @return string Container ID
     */
    protected function get_container_id() {
        return 'account-menu-tabs';
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
     * Register default form handlers for account actions.
     */
    private function register_default_handlers() {
        $this->register_form_handler('update_account', array($this, 'process_account_update'));
    }

    /**
     * Register a form handler callback for a specific action.
     * 
     * @param string   $action   The form action identifier
     * @param callable $callback The function to handle the form submission
     */
    public function register_form_handler($action, $callback) {
        if (is_callable($callback)) {
            $this->handlers[$action] = $callback;
        }
    }

    /**
     * Initialize the account interface.
     * 
     * Handles form submissions and message display.
     */
    public function init() {
        if (isset($_POST['wizard_form_action'])) {
            $this->handle_form_submission();
        }

        if (isset($_GET['wizard_message'])) {
            $message = get_transient('wizard_account_message_' . get_current_user_id());
            if ($message) {
                $this->messages[] = $message;
                delete_transient('wizard_account_message_' . get_current_user_id());
            }
        }
    }

    /**
     * Handle form submissions with proper nonce verification.
     */
    private function handle_form_submission() {
        $action = sanitize_key($_POST['wizard_form_action']);
        $nonce_key = 'wizard_' . $action . '_nonce';
        $nonce_action = 'wizard_' . $action;

        if (!isset($_POST[$nonce_key]) || !wp_verify_nonce($_POST[$nonce_key], $nonce_action)) {
            $this->add_message('error', 'Security verification failed.');
            return;
        }

        if (isset($this->handlers[$action])) {
            call_user_func($this->handlers[$action]);
        } else {
            $this->add_message('error', 'Invalid form action.');
        }
    }

    /**
     * Register the default account management tabs.
     */
    private function register_default_tabs() {
        $this->add_tab([
            'id' => 'account',
            'title' => 'Your Info',
            'icon' => 'fa-solid fa-user'
        ]);

        $this->add_tab([
            'id' => 'teams',
            'title' => 'Your Teams',
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
     * Handles both immediate display and redirect scenarios using transients.
     * 
     * @param string $type The message type ('error', 'success', etc.)
     * @param string $text The message text to display
     */
    private function add_message($type, $text) {
        $message = [
            'type' => $type,
            'text' => $text
        ];
        
        if (defined('DOING_AJAX') || headers_sent()) {
            $this->messages[] = $message;
        } else {
            set_transient('wizard_account_message_' . get_current_user_id(), $message, 60);
            wp_safe_redirect(add_query_arg('wizard_message', '1', remove_query_arg(array_keys($_GET))));
            exit;
        }
    }

    /**
     * Process account update form submissions.
     * 
     * Handles user data updates including:
     * - Basic user information (name, email)
     * - Password changes
     * - Avatar upload and deletion
     * 
     * Validates input data, processes file uploads, and updates user meta.
     * Redirects with appropriate success/error messages after processing.
     * 
     * @return void
     */
    public function process_account_update() {
        $user_id = $this->user->ID;
        $user_data = array();
        $updated = false;

        // Handle avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            // Check file type
            $file_type = wp_check_filetype($_FILES['avatar']['name']);
            if (!in_array($file_type['ext'], array('jpg', 'jpeg', 'png', 'gif'))) {
                $this->add_message('error', 'Invalid file type. Please upload an image file (JPG, PNG, or GIF).');
                return;
            }

            // Check file size (5MB limit)
            if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                $this->add_message('error', 'File is too large. Maximum size is 5MB.');
                return;
            }

            // Load required WordPress file handling functions
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
            }

            // Delete existing avatar if present
            $existing_avatar_id = get_user_meta($user_id, 'local_avatar', true);
            if ($existing_avatar_id) {
                wp_delete_attachment($existing_avatar_id, true);
            }

            // Upload and process the new avatar
            $avatar_id = media_handle_upload('avatar', 0);

            if (is_wp_error($avatar_id)) {
                $this->add_message('error', 'Failed to upload avatar: ' . $avatar_id->get_error_message());
                return;
            }

            update_user_meta($user_id, 'local_avatar', $avatar_id);
            $updated = true;
        }

        // Handle avatar deletion request
        if (isset($_POST['delete_avatar'])) {
            $avatar_id = get_user_meta($user_id, 'local_avatar', true);
            if ($avatar_id) {
                wp_delete_attachment($avatar_id, true);
                delete_user_meta($user_id, 'local_avatar');
                $updated = true;
            }
        }

        // Process basic user information updates
        if (isset($_POST['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
            update_user_meta($user_id, 'first_name', $user_data['first_name']);
            $updated = true;
        }

        if (isset($_POST['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
            update_user_meta($user_id, 'last_name', $user_data['last_name']);
            $updated = true;
        }

        if (isset($_POST['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($_POST['display_name']);
            $updated = true;
        }

        if (isset($_POST['user_email']) && is_email($_POST['user_email'])) {
            $user_data['user_email'] = sanitize_email($_POST['user_email']);
            $updated = true;
        }

        // Handle password update if provided
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            $user = get_user_by('id', $user_id);
            if (wp_check_password($_POST['current_password'], $user->data->user_pass, $user_id)) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $user_data['user_pass'] = $_POST['new_password'];
                    $updated = true;
                } else {
                    $this->add_message('error', 'New passwords do not match.');
                }
            } else {
                $this->add_message('error', 'Current password is incorrect.');
            }
        }

        // Update user data if changes were made
        if (!empty($user_data)) {
            $user_data['ID'] = $user_id;
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                $this->add_message('error', $result->get_error_message());
            } else {
                $updated = true;
            }
        }

        // Send success message if any updates were made
        if ($updated) {
            $this->add_message('success', 'Account information updated successfully.');
            return;
        }
    }
}
