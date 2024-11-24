<?php
// Initialize redirects
function init_wizard_redirects() {
    // Template redirects
    add_action('template_redirect', 'wizard_handle_build_template_redirect', 20);    

    // Folder Explorer redirects
    add_action('template_redirect', 'wizard_folder_redirects');
    add_action('template_redirect', 'wizard_template_search_redirect');
    add_action('template_redirect', 'wizard_check_invalid_pagination_redirect');

    // Account redirects
    add_action('template_redirect', 'wizard_account_form_redirects');

    // User redirects
    add_filter('wp_nav_menu_objects', 'wizard_filter_nav_menu_items_by_css_class', 10, 3);
}

add_action('init', 'init_wizard_redirects'); 