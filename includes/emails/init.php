<?php

namespace EmailWizard\Includes\Emails;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-email-manager.php';

// Initialize the email manager
$email_manager = EmailManager::get_instance();

// Load template IDs from options
$template_ids = get_option('wizard_email_templates', []);
foreach ($template_ids as $type => $id) {
    if (!empty($id)) {
        $email_manager->set_template_id($type, $id);
    }
}
 