<?php
function wizard_account_form_redirects() {
    // Only run on account page
    if (!is_page(get_option('wizard_account_page_id'))) {
        return;
    }

    // Check if form was submitted and successfully processed
    if (isset($_GET['account_updated']) && $_GET['account_updated'] === 'true') {
        // Clear the URL parameter to prevent resubmission
        wp_safe_redirect(remove_query_arg('account_updated'));
        exit;
    }
} 