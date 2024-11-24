<?php
function wizard_account_form_redirects() {
    // Only run on account page
    if (!is_page(get_option('wizard_account_page_id'))) {
        return;
    }

    // Check if form was submitted
    if (isset($_POST['wizard_account_action']) && $_POST['wizard_account_action'] === 'update_account') {
        // Verify nonce
        if (!isset($_POST['wizard_account_nonce']) || 
            !wp_verify_nonce($_POST['wizard_account_nonce'], 'wizard_account_info')) {
            return;
        }

        // Only redirect if there's no file upload
        if (empty($_FILES['avatar']['name'])) {
            // Process the form submission
            $account = new WizardAccount();
            $account->process_account_update();

            // Store any messages that were generated during processing
            if (!empty($account->messages)) {
                $_SESSION['wizard_account_messages'] = $account->messages;
            }

            // Store current URL for redirect
            $redirect_url = add_query_arg('account_updated', 'true', wp_get_referer());
            
            // Perform redirect
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
} 