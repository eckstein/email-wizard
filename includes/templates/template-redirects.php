<?php

// Handle build-template redirects
function wizard_handle_build_template_redirect() {
    global $wp_query, $wp;

    if (isset($wp_query->query_vars['build-wizard-template'])) {
        $current_url = home_url(add_query_arg(array(), $wp->request));
        if (strpos($current_url, '/build-wizard-template/') !== false && !isset($_SERVER['HTTP_REFERER'])) {
            wp_die('Direct access to the template builder endpoint is not allowed!');
            exit;
        }
        exit;
    }
}

// Legacy function - can be removed if not needed elsewhere
function idemailwiz_handle_builder_v2_request() {
    // Empty function that can be removed if not used
}
