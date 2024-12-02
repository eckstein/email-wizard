<?php
// Initialize redirects
function init_wizard_redirects() { 

    // Folder Explorer redirects
    add_action('template_redirect', 'wizard_folder_redirects');
    add_action('template_redirect', 'wizard_template_search_redirect');
    add_action('template_redirect', 'wizard_check_invalid_pagination_redirect');

    // Account redirects
    add_action('template_redirect', 'wizard_account_form_redirects');
}

add_action('init', 'init_wizard_redirects');
