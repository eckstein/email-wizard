<?php

if (!defined('ABSPATH')) {
    exit;
}

// Register email template settings
function wizard_email_settings_init() {
    // Register API key setting
    register_setting('wizard_options', 'wizard_brevo_api_key', [
        'type' => 'string',
        'description' => 'Brevo API Key',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);

    register_setting('wizard_options', 'wizard_email_templates', [
        'type' => 'array',
        'description' => 'Email template IDs for Brevo',
        'sanitize_callback' => 'wizard_sanitize_template_ids',
        'default' => []
    ]);

    // Add API key section
    add_settings_section(
        'wizard_brevo_api_section',
        __('Brevo API Configuration', 'wizard'),
        'wizard_brevo_api_section_callback',
        'wizard-email-templates'
    );

    // Add API key field
    add_settings_field(
        'wizard_brevo_api_key',
        __('API Key', 'wizard'),
        'wizard_render_api_key_field',
        'wizard-email-templates',
        'wizard_brevo_api_section'
    );

    add_settings_section(
        'wizard_email_templates_section',
        __('Email Templates', 'wizard'),
        'wizard_email_templates_section_callback',
        'wizard-email-templates'
    );

    $template_types = [
        'reset_password' => 'Password Reset',
        'new_account' => 'New Account',
        'email_change_attempt' => 'Email Change Attempt',
        'email_change_confirm' => 'Email Change Confirmation',
        'email_changed' => 'Email Changed Notification',
        'personal_data_request' => 'Personal Data Request',
        'team_invitation' => 'Team Invitation',
        'team_invitation_accepted' => 'Team Invitation Accepted'
    ];

    foreach ($template_types as $type => $label) {
        add_settings_field(
            "template_$type",
            __($label, 'wizard'),
            'wizard_render_template_field',
            'wizard-email-templates',
            'wizard_email_templates_section',
            ['type' => $type]
        );
    }
}
add_action('admin_init', 'wizard_email_settings_init');

// Section description callback
function wizard_email_templates_section_callback() {
    echo '<p>' . __('Configure your Brevo email template IDs for each type of email. You can find these IDs in your Brevo dashboard under Templates.', 'wizard') . '</p>';
}

// Template field callback
function wizard_render_template_field($args) {
    $type = $args['type'];
    $options = get_option('wizard_email_templates', []);
    $value = isset($options[$type]) ? $options[$type] : '';
    ?>
    <div class="template-field-wrapper">
        <input type="text" 
               name="<?php echo esc_attr("wizard_email_templates[$type]"); ?>"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="Enter template ID">
        <button type="button" 
                class="button test-template" 
                data-template-type="<?php echo esc_attr($type); ?>"
                <?php echo empty($value) ? 'disabled' : ''; ?>>
            <?php _e('Test Email', 'wizard'); ?>
        </button>
        <span class="spinner" style="float: none; margin: 0 0 0 4px;"></span>
        <p class="description">
            <?php echo wizard_get_template_description($type); ?>
        </p>
    </div>
    <?php
}

// Get template description
function wizard_get_template_description($type) {
    $descriptions = [
        'reset_password' => __('Sent when a user requests a password reset.', 'wizard'),
        'new_account' => __('Sent to new users when their account is created.', 'wizard'),
        'email_change_attempt' => __('Sent to the current email address when a user attempts to change it.', 'wizard'),
        'email_change_confirm' => __('Sent to the new email address for confirmation.', 'wizard'),
        'email_changed' => __('Sent after an email address has been successfully changed.', 'wizard'),
        'personal_data_request' => __('Sent when a user requests their personal data.', 'wizard'),
        'team_invitation' => __('Sent when a user is invited to join a team.', 'wizard'),
        'team_invitation_accepted' => __('Sent to team admin when an invitation is accepted.', 'wizard')
    ];
    return isset($descriptions[$type]) ? $descriptions[$type] : '';
}

// Sanitize template IDs
function wizard_sanitize_template_ids($input) {
    if (!is_array($input)) {
        return [];
    }

    return array_map('sanitize_text_field', $input);
}

// Handle test email AJAX request
function wizard_handle_test_email() {
    check_ajax_referer('test_email_template', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wizard')]);
    }

    $type = sanitize_text_field($_POST['template_type'] ?? '');
    $template_types = [
        'reset_password', 'new_account', 'email_change_attempt',
        'email_change_confirm', 'email_changed', 'personal_data_request',
        'team_invitation', 'team_invitation_accepted'
    ];

    if (!in_array($type, $template_types)) {
        wp_send_json_error(['message' => __('Invalid template type specified.', 'wizard')]);
    }

    $current_user = wp_get_current_user();
    $email_manager = \EmailWizard\Includes\Emails\EmailManager::get_instance();
    
    // Prepare test data
    $test_data = wizard_get_test_email_data($type, $current_user);
    
    // Validate template ID
    if (empty($test_data['template_id'])) {
        wp_send_json_error([
            'message' => __('No template ID found. Please save a template ID before testing.', 'wizard')
        ]);
    }

    try {
        // Add error handling filter
        add_filter('wp_mail_failed', 'wizard_handle_email_error');
        
        $result = $email_manager->send_transactional_email(
            $test_data['template_id'],
            $current_user->user_email,
            $test_data['data']
        );

        // Remove error handling filter
        remove_filter('wp_mail_failed', 'wizard_handle_email_error');

        if ($result) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Test email sent successfully to %s using template ID %s', 'wizard'),
                    $current_user->user_email,
                    $test_data['template_id']
                )
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to send test email. Please check your SMTP settings in WP Mail SMTP.', 'wizard'),
                'debug' => [
                    'template_id' => $test_data['template_id'],
                    'recipient' => $current_user->user_email,
                    'type' => $type
                ]
            ]);
        }
    } catch (\Exception $e) {
        wp_send_json_error([
            'message' => sprintf(
                __('Error sending email: %s', 'wizard'),
                $e->getMessage()
            ),
            'debug' => [
                'template_id' => $test_data['template_id'],
                'recipient' => $current_user->user_email,
                'type' => $type,
                'error' => $e->getMessage()
            ]
        ]);
    }
}
add_action('wp_ajax_test_email_template', 'wizard_handle_test_email');

// Handle email errors
function wizard_handle_email_error($error) {
    if (is_wp_error($error)) {
        wp_send_json_error([
            'message' => sprintf(
                __('Mail error: %s', 'wizard'),
                $error->get_error_message()
            ),
            'debug' => [
                'code' => $error->get_error_code(),
                'data' => $error->get_error_data()
            ]
        ]);
    }
    return $error;
}

// Get test email data
function wizard_get_test_email_data($type, $user) {
    $options = get_option('wizard_email_templates', []);
    $template_id = $options[$type] ?? '';

    $base_data = [
        'user_login' => $user->user_login,
        'sitename' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)
    ];

    $specific_data = [
        'reset_password' => [
            'reset_link' => network_site_url("wp-login.php?action=rp&key=TEST_KEY&login=" . rawurlencode($user->user_login), 'login')
        ],
        'email_change_attempt' => [
            'old_email' => $user->user_email,
            'new_email' => 'new.' . $user->user_email
        ],
        'email_change_confirm' => [
            'old_email' => $user->user_email,
            'new_email' => 'new.' . $user->user_email,
            'confirm_link' => esc_url(admin_url('profile.php?newuseremail=new.' . $user->user_email))
        ],
        'email_changed' => [
            'old_email' => 'old.' . $user->user_email,
            'new_email' => $user->user_email
        ],
        'personal_data_request' => [
            'request_type' => 'export_personal_data',
            'confirm_url' => esc_url(admin_url('tools.php?page=export_personal_data'))
        ]
    ];

    return [
        'template_id' => $template_id,
        'data' => array_merge(
            $base_data,
            $specific_data[$type] ?? []
        )
    ];
}

// Enqueue scripts
function wizard_email_settings_scripts($hook) {
    if (!in_array($hook, ['toplevel_page_wizard-options', 'wizard_page_wizard-email-templates'])) {
        return;
    }

    wp_enqueue_script(
        'wizard-email-settings',
        plugins_url('admin/js/email-settings.js', dirname(__FILE__)),
        ['jquery'],
        defined('EMAILWIZARD_VERSION') ? EMAILWIZARD_VERSION : '1.0.0',
        true
    );

    wp_localize_script('wizard-email-settings', 'wizardEmailSettings', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('test_email_template'),
        'strings' => [
            'testSuccess' => __('Test email sent successfully!', 'wizard'),
            'testError' => __('Error sending test email. Please try again.', 'wizard')
        ]
    ]);
}
add_action('admin_enqueue_scripts', 'wizard_email_settings_scripts');

// API section callback
function wizard_brevo_api_section_callback() {
    echo '<p>' . __('Enter your Brevo API key to enable template-based emails. You can find your API key in your Brevo account under SMTP & API > API Keys.', 'wizard') . '</p>';
}

// API key field callback
function wizard_render_api_key_field() {
    $api_key = get_option('wizard_brevo_api_key', '');
    ?>
    <input type="password" 
           name="wizard_brevo_api_key" 
           value="<?php echo esc_attr($api_key); ?>"
           class="regular-text"
           autocomplete="new-password">
    <p class="description">
        <?php _e('Your Brevo API key (v3). Keep this secret!', 'wizard'); ?>
    </p>
    <?php
} 