<?php
function wizard_account_form_redirects() {
    global $wizard_account_page;
    // Only run on account page
    if (!$wizard_account_page) {
        return;
    }

    // Remove wizard_message parameter after it's been processed
    if (isset($_GET['wizard_message'])) {
        $current_tab = isset($_GET['tab']) ? '?tab=' . $_GET['tab'] : '';
        wp_safe_redirect(remove_query_arg('wizard_message') . $current_tab);
        exit;
    }
} 