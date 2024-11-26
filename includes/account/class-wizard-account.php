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
     * Register default form handlers for account actions.
     * Handlers match the value of the hidden input field name="wizard_form_action" in each tab template file form.
     * Example: <input type="hidden" name="wizard_form_action" value="update_account">
     */
    private function register_default_handlers()
    {
        $this->register_form_handler('update_account', array($this, 'process_account_update'));
        // add more handlers for more forms in other tabs here when needed
    }

    /**
     * Register a form handler callback for a specific action.
     * 
     * @param string   $action   The form action identifier
     * @param callable $callback The function to handle the form submission
     */
    public function register_form_handler($action, $callback)
    {
        if (is_callable($callback)) {
            $this->handlers[$action] = $callback;
        }
    }

    /**
     * Handle form submissions with proper nonce verification.
     */
    private function handle_form_submission()
    {
        $action = sanitize_key($_POST['wizard_form_action']);

        $processor = new WizardFormValidator($action);

        if (!$processor->verify_nonce()) {
            $this->add_message('error', 'Security verification failed.');
            return;
        }

        if (isset($this->handlers[$action])) {
            call_user_func($this->handlers[$action], $processor);
        } else {
            $this->add_message('error', 'Invalid form action.');
        }
    }

    /**
     * Process account update form submissions.
     * 
     * @param WizardFormValidator $processor Form processor instance
     */
    public function process_account_update($processor) {
        $user_id = $this->user->ID;
        $updated = false;

        // Define field validation rules
        $fields = [
            'first_name' => ['type' => 'text'],
            'last_name' => ['type' => 'text'],
            'display_name' => ['type' => 'text'],
            'user_email' => ['type' => 'email'],
            'current_password' => ['type' => 'password'],
            'new_password' => ['type' => 'password', 'confirm_field' => 'confirm_password']
        ];

        // Process standard fields
        if ($processor->process_fields($fields)) {
            $data = $processor->get_data();
            
            // Handle avatar upload if present
            if (!empty($_FILES['avatar']['name'])) {
                $avatar_id = $processor->process_file('avatar', 
                    ['jpg', 'jpeg', 'png', 'gif'], 
                    5 * 1024 * 1024
                );

                if ($avatar_id && !is_wp_error($avatar_id)) {
                    $existing_avatar_id = get_user_meta($user_id, 'local_avatar', true);
                    if ($existing_avatar_id) {
                        wp_delete_attachment($existing_avatar_id, true);
                    }
                    update_user_meta($user_id, 'local_avatar', $avatar_id);
                    $updated = true;
                }
            }

            // Handle avatar deletion
            if (isset($_POST['delete_avatar'])) {
                $avatar_id = get_user_meta($user_id, 'local_avatar', true);
                if ($avatar_id) {
                    wp_delete_attachment($avatar_id, true);
                    delete_user_meta($user_id, 'local_avatar');
                    $updated = true;
                }
            }

            // Update user data
            $user_data = array_intersect_key($data, array_flip(['first_name', 'last_name', 'display_name', 'user_email']));
            
            // Handle password update
            if (!empty($data['current_password']) && !empty($data['new_password'])) {
                if (wp_check_password($data['current_password'], $this->user->data->user_pass, $user_id)) {
                    $user_data['user_pass'] = $data['new_password'];
                } else {
                    $this->add_message('error', 'Current password is incorrect.');
                    return;
                }
            }

            if (!empty($user_data)) {
                $user_data['ID'] = $user_id;
                $result = wp_update_user($user_data);
                
                if (is_wp_error($result)) {
                    $this->add_message('error', $result->get_error_message());
                    return;
                }
                $updated = true;
            }

            if ($updated) {
                $this->add_message('success', 'Account information updated successfully.');
            }
        } else {
            foreach ($processor->get_errors() as $error) {
                $this->add_message('error', $error);
            }
        }
    }

    /**
     * Add a message to be displayed to the user.
     * 
     * Handles both immediate display and redirect scenarios using transients.
     * 
     * @param string $type The message type ('error', 'success', etc.)
     * @param string $text The message text to display
     */
    private function add_message($type, $text)
    {
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
}
