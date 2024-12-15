<?php

namespace EmailWizard\Includes\Emails;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Manager Class
 * Handles all email operations including WordPress default emails and custom transactional emails
 */
class EmailManager {
    /**
     * @var null|EmailManager
     */
    private static $instance = null;

    /**
     * @var string Brevo API key
     */
    private $api_key;

    /**
     * @var string Brevo API endpoint
     */
    private $api_endpoint = 'https://api.brevo.com/v3/smtp/email';

    /**
     * Brevo template IDs for WordPress default emails
     */
    private $template_ids = [
        'reset_password' => null,
        'new_account' => null,
        'email_change_attempt' => null,
        'email_change_confirm' => null,
        'email_changed' => null,
        'personal_data_request' => null,
        'team_invitation' => null,
        'team_invitation_accepted' => null
    ];

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : get_option('wizard_brevo_api_key');
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Filter WordPress emails before they're sent
        add_filter('wp_mail', [$this, 'intercept_wp_mail'], 10, 1);
        
        // Specific email filters
        add_filter('retrieve_password_message', [$this, 'customize_reset_password_email'], 10, 4);
        add_filter('wp_new_user_notification_email', [$this, 'customize_new_account_email'], 10, 3);
        add_filter('email_change_email', [$this, 'customize_email_change_email'], 10, 2);
        add_filter('email_confirmation_notification', [$this, 'customize_email_change_notification'], 10, 2);
        add_filter('password_change_email', [$this, 'customize_email_changed_notification'], 10, 3);
        add_filter('user_request_action_email_content', [$this, 'customize_data_request_email'], 10, 2);
    }

    /**
     * Intercept WordPress emails before they're sent
     * 
     * @param array $args
     * @return array
     */
    public function intercept_wp_mail($args) {
        // Here we can modify any email before it's sent
        return $args;
    }

    /**
     * Send a custom transactional email using Brevo's API
     * 
     * @param string $template_id Brevo template ID
     * @param array|string $to Recipient email(s)
     * @param array $data Template variables
     * @return bool
     * @throws \Exception If validation fails
     */
    public function send_transactional_email($template_id, $to, $data = []) {
        // Validate inputs
        if (empty($template_id)) {
            throw new \Exception('Template ID is required');
        }

        if (empty($to)) {
            throw new \Exception('Recipient email is required');
        }

        if (empty($this->api_key)) {
            throw new \Exception('Brevo API key is not configured');
        }

        // Ensure template ID is numeric
        if (!is_numeric($template_id)) {
            throw new \Exception('Invalid template ID format. Brevo template IDs should be numbers.');
        }

        // Format recipient
        $to = is_array($to) ? $to[0] : $to;

        // Prepare the API request
        $body = [
            'templateId' => (int)$template_id,
            'to' => [
                [
                    'email' => $to
                ]
            ],
            'params' => $data
        ];

        // Make the API request
        $response = wp_remote_post($this->api_endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'api-key' => $this->api_key
            ],
            'body' => json_encode($body),
            'timeout' => 15
        ]);

        // Handle the response
        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 201) {
            $error_message = isset($response_body['message']) 
                ? $response_body['message'] 
                : 'Unknown error occurred';
            throw new \Exception('API error: ' . $error_message);
        }

        return true;
    }

    /**
     * Customize reset password email
     */
    public function customize_reset_password_email($message, $key, $user_login, $user_data) {
        if ($this->template_ids['reset_password']) {
            $this->send_transactional_email(
                $this->template_ids['reset_password'],
                $user_data->user_email,
                [
                    'user_login' => $user_login,
                    'reset_link' => network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login')
                ]
            );
            return ''; // Return empty to prevent default email
        }
        return $message;
    }

    /**
     * Customize new account email
     */
    public function customize_new_account_email($wp_new_user_notification_email, $user, $blogname) {
        if ($this->template_ids['new_account']) {
            $this->send_transactional_email(
                $this->template_ids['new_account'],
                $user->user_email,
                [
                    'user_login' => $user->user_login,
                    'blogname' => $blogname
                ]
            );
            return [
                'to' => '',
                'subject' => '',
                'message' => '',
                'headers' => ''
            ];
        }
        return $wp_new_user_notification_email;
    }

    /**
     * Customize email change attempt notification
     * Sent to the old email address when a user attempts to change their email
     * 
     * @param array $email_data The default email data
     * @param array|WP_User $user User data
     * @return array|string Empty array/string to prevent default email if using template
     */
    public function customize_email_change_email($email_data, $user) {
        if ($this->template_ids['email_change_attempt']) {
            // Handle both array and object user data formats
            $user_email = is_array($user) ? $user['user_email'] : $user->user_email;
            $user_login = is_array($user) ? $user['user_login'] : $user->user_login;
            $new_email = is_array($user) ? $user['newemail'] : $user->newemail;

            $this->send_transactional_email(
                $this->template_ids['email_change_attempt'],
                $user_email, // Send to current email
                [
                    'user_login' => $user_login,
                    'old_email' => $user_email,
                    'new_email' => $new_email
                ]
            );
            return [
                'to' => '',
                'subject' => '',
                'message' => '',
                'headers' => ''
            ];
        }
        return $email_data;
    }

    /**
     * Customize email change confirmation notification
     * Sent to the new email address for confirmation
     * 
     * @param array $email_data The default email data
     * @param array|WP_User $user User data
     * @return string Empty string to prevent default email if using template
     */
    public function customize_email_change_notification($email_data, $user) {
        if ($this->template_ids['email_change_confirm']) {
            // Handle both array and object user data formats
            $user_email = is_array($user) ? $user['user_email'] : $user->user_email;
            $user_login = is_array($user) ? $user['user_login'] : $user->user_login;
            $new_email = is_array($user) ? $user['newemail'] : $user->newemail;

            $this->send_transactional_email(
                $this->template_ids['email_change_confirm'],
                $new_email, // Send to new email
                [
                    'user_login' => $user_login,
                    'old_email' => $user_email,
                    'new_email' => $new_email,
                    'confirm_link' => esc_url(admin_url('profile.php?newuseremail=' . $new_email))
                ]
            );
            return '';
        }
        return $email_data;
    }

    /**
     * Customize email changed notification
     * Sent after email has been successfully changed
     * 
     * @param array $email_data The default email data
     * @param array|WP_User $user User data
     * @param mixed $userdata Additional user data (if provided)
     * @return array Empty array to prevent default email if using template
     */
    public function customize_email_changed_notification($email_data, $user, $userdata = null) {
        if ($this->template_ids['email_changed']) {
            // Handle both array and object user data formats
            $user_email = is_array($user) ? $user['user_email'] : $user->user_email;
            $user_login = is_array($user) ? $user['user_login'] : $user->user_login;
            $old_email = $userdata ? $userdata->user_email : $user_email;

            $this->send_transactional_email(
                $this->template_ids['email_changed'],
                $user_email, // Send to new email
                [
                    'user_login' => $user_login,
                    'old_email' => $old_email,
                    'new_email' => $user_email
                ]
            );
            return [
                'to' => '',
                'subject' => '',
                'message' => '',
                'headers' => ''
            ];
        }
        return $email_data;
    }

    /**
     * Customize data request email
     */
    public function customize_data_request_email($content, $email_data) {
        if ($this->template_ids['personal_data_request']) {
            $this->send_transactional_email(
                $this->template_ids['personal_data_request'],
                $email_data['user_email'],
                [
                    'user_login' => $email_data['username'],
                    'request_type' => $email_data['type_of_data'],
                    'confirm_url' => $email_data['confirm_url'],
                    'sitename' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)
                ]
            );
            return '';
        }
        return $content;
    }

    /**
     * Set template ID for a specific email type
     * 
     * @param string $type Email type
     * @param string $template_id Brevo template ID
     */
    public function set_template_id($type, $template_id) {
        if (array_key_exists($type, $this->template_ids)) {
            $this->template_ids[$type] = $template_id;
        }
    }

    /**
     * Send team invitation email
     * 
     * @param string $to_email Invitee's email address
     * @param array $data Invitation data
     * @return bool
     */
    public function send_team_invitation($to_email, $data) {
        if (!$this->template_ids['team_invitation']) {
            return false;
        }

        try {
            return $this->send_transactional_email(
                $this->template_ids['team_invitation'],
                $to_email,
                array_merge([
                    'sitename' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)
                ], $data)
            );
        } catch (\Exception $e) {
            error_log('Failed to send team invitation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send team invitation accepted notification
     * 
     * @param string $to_email Team admin's email address
     * @param array $data Acceptance data
     * @return bool
     */
    public function send_team_invitation_accepted($to_email, $data) {
        if (!$this->template_ids['team_invitation_accepted']) {
            return false;
        }

        try {
            return $this->send_transactional_email(
                $this->template_ids['team_invitation_accepted'],
                $to_email,
                array_merge([
                    'sitename' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)
                ], $data)
            );
        } catch (\Exception $e) {
            error_log('Failed to send team invitation acceptance: ' . $e->getMessage());
            return false;
        }
    }
} 