<?php
// Initialize the account page
add_action('template_redirect', function() {
    global $wizard_account_page;
    if ($wizard_account_page) {
        $account = new WizardAccount();
        $account->init();
    }
});

require_once plugin_dir_path(dirname(__FILE__)) . 'interface/class-wizard-tabs.php';

class WizardAccount extends WizardTabs
{
    private $user;

    public function __construct()
    {
        parent::__construct();
        
        if ($this->is_authorized) {
            $this->user = wp_get_current_user();
            $this->register_default_tabs();
            $this->register_default_handlers();
        }
    }

    protected function get_container_id()
    {
        return 'account-menu-tabs';
    }

    private function register_default_handlers()
    {
        $this->register_form_handler('update_account', array($this, 'process_account_update'));
        // Future handlers will be registered here
        // $this->register_form_handler('update_team', array($this, 'process_team_update'));
    }

    public function register_form_handler($action, $callback)
    {
        if (is_callable($callback)) {
            $this->handlers[$action] = $callback;
        }
    }

    public function init()
    {
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

    private function handle_form_submission()
    {
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

    private function register_default_tabs()
    {
        $this->add_tab([
            'id' => 'account',
            'title' => 'Your Info',
            'icon' => 'fa-solid fa-user',
            'template' => 'tab-account.php'
        ]);

        $this->add_tab([
            'id' => 'teams',
            'title' => 'Your Teams',
            'icon' => 'fa-solid fa-users',
            'template' => 'tab-teams.php'
        ]);

        $this->add_tab([
            'id' => 'plan',
            'title' => 'Manage Plan',
            'icon' => 'fa-solid fa-sliders',
            'template' => 'tab-plan.php'
        ]);

        $this->add_tab([
            'id' => 'billing',
            'title' => 'Billing Settings',
            'icon' => 'fa-solid fa-credit-card',
            'template' => 'tab-billing.php'
        ]);
    }

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

    public function process_account_update()
    {
        $user_id = $this->user->ID;
        $user_data = array();
        $updated = false;

        // Handle avatar upload first
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

            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
            }

            // Delete existing avatar first
            $existing_avatar_id = get_user_meta($user_id, 'local_avatar', true);
            if ($existing_avatar_id) {
                wp_delete_attachment($existing_avatar_id, true);
            }

            // Upload the file
            $avatar_id = media_handle_upload('avatar', 0);

            if (is_wp_error($avatar_id)) {
                $this->add_message('error', 'Failed to upload avatar: ' . $avatar_id->get_error_message());
                return;
            }

            // Update user meta with new avatar
            update_user_meta($user_id, 'local_avatar', $avatar_id);
            
            $updated = true;
        }

        // Handle avatar deletion if requested
        if (isset($_POST['delete_avatar'])) {
            $avatar_id = get_user_meta($user_id, 'local_avatar', true);
            if ($avatar_id) {
                wp_delete_attachment($avatar_id, true);
                delete_user_meta($user_id, 'local_avatar');
                $updated = true;
            }
        }

        // Handle basic user data updates
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

        // Update user data if we have changes
        if (!empty($user_data)) {
            $user_data['ID'] = $user_id;
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                $this->add_message('error', $result->get_error_message());
            } else {
                $updated = true;
            }
        }

        // Update success message handling
        if ($updated) {
            $this->add_message('success', 'Account information updated successfully.');
            return; // The add_message method will handle the redirect
        }
    }
}
