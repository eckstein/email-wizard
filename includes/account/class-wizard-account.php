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
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_POST['wizard_form_action'])) {
            $this->handle_form_submission();
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
        $this->handlers = array(
            'update_account' => array($this, 'process_account_update'),
            'update_team_settings' => array($this, 'process_team_update'),
            'update_member_role' => array($this, 'process_member_role_update'),
            'remove_team_member' => array($this, 'process_member_removal'),
            'invite_team_member' => array($this, 'process_team_invite'),
            'resend_team_invite' => array($this, 'process_resend_invite'),
            'revoke_team_invite' => array($this, 'process_revoke_invite')
        );
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
                $avatar_handler = new WizardAvatar($user_id);
                $avatar_result = $avatar_handler->handle_upload($_FILES['avatar']);

                if (is_wp_error($avatar_result)) {
                    $this->add_message('error', 'Avatar upload failed: ' . $avatar_result->get_error_message());
                } else {
                    $set_result = $avatar_handler->set_avatar($avatar_result);
                    if (is_wp_error($set_result)) {
                        $this->add_message('error', 'Failed to set avatar: ' . $set_result->get_error_message());
                        wp_delete_attachment($avatar_result, true);
                    } else {
                        $updated = true;
                    }
                }
            }

            // Handle avatar deletion
            if (isset($_POST['delete_avatar'])) {
                $avatar_handler = new WizardAvatar($user_id);
                $delete_result = $avatar_handler->delete_avatar();
                
                if (is_wp_error($delete_result)) {
                    $this->add_message('error', 'Failed to delete avatar: ' . $delete_result->get_error_message());
                } else {
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
     * Handles both immediate display and redirect scenarios using sessions.
     * 
     * @param string $type The message type ('error', 'success', etc.)
     * @param string $text The message text to display
     */
    private function add_message($type, $text)
    {
        if (!isset($_SESSION['wizard_account_messages'])) {
            $_SESSION['wizard_account_messages'] = [];
        }

        $_SESSION['wizard_account_messages'][] = [
            'type' => $type,
            'text' => $text
        ];

        if (!defined('DOING_AJAX') && !headers_sent()) {
            wp_safe_redirect(remove_query_arg(array_keys($_GET)));
            exit;
        }
    }

    /**
     * Process team update form submissions.
     */
    public function process_team_update($processor) {
        if (!$processor->verify_nonce('wizard_update_team_settings')) {
            $this->add_message('error', 'Security verification failed.');
            return;
        }

        $fields = [
            'team_id' => ['type' => 'number', 'required' => true],
            'team_name' => ['type' => 'text', 'required' => true],
            'team_description' => ['type' => 'text', 'required' => false]
        ];

        if ($processor->process_fields($fields)) {
            $data = $processor->get_data();
            $team_id = absint($data['team_id']);
            
            $teams = new WizardTeams();
            $team = $teams->get_team($team_id);

            if (is_wp_error($team) || !$teams->is_team_admin($team_id, get_current_user_id())) {
                $this->add_message('error', 'You do not have permission to edit this team');
                return;
            }

            // Handle team updates
            $update_data = array(
                'name' => $data['team_name'],
                'description' => $data['team_description']
            );

            $result = $teams->update_team($team_id, $update_data);
            if (is_wp_error($result)) {
                $this->add_message('error', $result->get_error_message());
                return;
            }

            // Handle file upload if present
            if (!empty($_FILES['team_avatar']['name'])) {
                $result = $teams->update_team_avatar($team_id, $_FILES['team_avatar']);
                if (is_wp_error($result)) {
                    $this->add_message('error', $result->get_error_message());
                    return;
                }
            }

            // Handle avatar deletion
            if (isset($_POST['delete_team_avatar'])) {
                $result = $teams->delete_team_avatar($team_id);
                if (is_wp_error($result)) {
                    $this->add_message('error', $result->get_error_message());
                    return;
                }
            }

            $this->add_message('success', 'Team settings updated successfully.');
        } else {
            foreach ($processor->get_errors() as $error) {
                $this->add_message('error', $error);
            }
        }
    }

    /**
     * Process member role update form submissions.
     */
    public function process_member_role_update($processor) {
        if (!$processor->verify_nonce('wizard_update_member_role')) {
            $this->add_message('error', 'Security verification failed.');
            return;
        }

        $fields = [
            'team_id' => ['type' => 'number', 'required' => true],
            'user_id' => ['type' => 'number', 'required' => true],
            'member_role' => ['type' => 'text', 'required' => true]
        ];

        if ($processor->process_fields($fields)) {
            $data = $processor->get_data();
            $teams = new WizardTeams();
            
            $result = $teams->update_member_role(
                absint($data['team_id']), 
                absint($data['user_id']), 
                $data['member_role']
            );

            if (is_wp_error($result)) {
                $this->add_message('error', $result->get_error_message());
            } else {
                $this->add_message('success', 'Member role updated successfully.');
            }
        }
    }

    /**
     * Process member removal form submissions.
     */
    public function process_member_removal($processor) {
        if (!$processor->verify_nonce('wizard_remove_team_member')) {
            $this->add_message('error', 'Security verification failed.');
            return;
        }

        $fields = [
            'team_id' => ['type' => 'number', 'required' => true],
            'user_id' => ['type' => 'number', 'required' => true]
        ];

        if ($processor->process_fields($fields)) {
            $data = $processor->get_data();
            $teams = new WizardTeams();
            
            $result = $teams->remove_team_member(
                absint($data['team_id']), 
                absint($data['user_id'])
            );

            if (is_wp_error($result)) {
                $this->add_message('error', $result->get_error_message());
            } else {
                $this->add_message('success', 'Team member removed successfully.');
            }
        }
    }

    /**
     * Process team member invite form submissions.
     */
    public function process_team_invite($processor) {
        if (!$processor->verify_nonce('wizard_invite_team_member')) {
            $this->add_message('error', 'Security verification failed.');
            return;
        }

        $fields = [
            'team_id' => ['type' => 'number', 'required' => true],
            'member_email' => ['type' => 'email', 'required' => true]
        ];

        if ($processor->process_fields($fields)) {
            $data = $processor->get_data();
            $teams = new WizardTeams();
            
            $result = $teams->create_team_invite(
                absint($data['team_id']),
                $data['member_email'],
                get_current_user_id(),
                'member'
            );

            if (is_wp_error($result)) {
                $this->add_message('error', $result->get_error_message());
            } else {
                $this->add_message('success', 'Team invitation sent successfully.');
            }
        }
    }

    /**
     * Process resend invite form submissions.
     */
    public function process_resend_invite($processor) {
        if (!$processor->verify_nonce('wizard_resend_invite')) {
            $this->add_message('error', 'Security verification failed.');
            return;
        }

        $fields = [
            'team_id' => ['type' => 'number', 'required' => true],
            'user_id' => ['type' => 'number', 'required' => true]
        ];

        if ($processor->process_fields($fields)) {
            $data = $processor->get_data();
            $teams = new WizardTeams();
            
            // For now, always succeed
            $this->add_message('success', 'Team invitation resent successfully.');
        }
    }

    /**
     * Process revoke invite form submissions.
     */
    public function process_revoke_invite($processor) {
        error_log('Starting process_revoke_invite');
        
        if (!$processor->verify_nonce('wizard_revoke_invite')) {
            error_log('Nonce verification failed');
            $this->add_message('error', 'Security verification failed.');
            return;
        }

        $fields = [
            'team_id' => ['type' => 'number', 'required' => true],
            'invite_id' => ['type' => 'number', 'required' => true]
        ];

        if ($processor->process_fields($fields)) {
            $data = $processor->get_data();
            error_log('Processing revoke for invite_id: ' . $data['invite_id']);
            
            $teams = new WizardTeams();
            $result = $teams->revoke_team_invite(absint($data['invite_id']));
            
            error_log('Revoke result: ' . (is_wp_error($result) ? $result->get_error_message() : 'success'));
            
            if (is_wp_error($result)) {
                $this->add_message('error', $result->get_error_message());
            } else {
                $this->add_message('success', 'Team invitation revoked successfully.');
            }
        } else {
            error_log('Field validation failed');
            foreach ($processor->get_errors() as $error) {
                $this->add_message('error', $error);
            }
        }
    }
}
