<?php
//Keep wizard users out of admin
add_action('admin_init', 'redirect_wizard_user_from_admin');
function redirect_wizard_user_from_admin()
{
    if (current_user_can('wizard_user') && ! current_user_can('administrator') && is_admin()) {
        wp_redirect(home_url());
        exit;
    }
}
// Filter nav menu items based on user status
function wizard_filter_nav_menu_items_by_css_class($items, $args) {
    foreach ($items as $key => $item) {
        if (in_array('logged-in-only', $item->classes) && !is_user_logged_in()) {
            unset($items[$key]);
        }
        if (in_array('logged-out-only', $item->classes) && is_user_logged_in()) {
            unset($items[$key]);
        }
        if (in_array('wizard-user-only', $item->classes) && !current_user_can('wizard_user')) {
            unset($items[$key]);
        }
    }
    return $items;
}
