<?php
// Creates a custom AJAX endpoint
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function wiz_ajax_redirect()
{
    if (get_query_var('wiz_ajax_endpoint')) {
        process_custom_wiz_request();
        exit;
    }
}
add_action('template_redirect', 'wiz_ajax_redirect', 11);

function process_custom_wiz_request()
{
    // Ensure this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_send_json_error('Invalid request method');
    }

    // Verify nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wizard_security')) {
        wp_send_json_error('Security check failed');
    }

    // Get the action
    $action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';

    // Dispatch to the appropriate handler
    do_action("wiz_ajax_{$action}", $_POST);

    // If we reach here, it means no action was taken
    wp_send_json_error('Invalid action');
}

add_action('init', 'wiz_add_custom_endpoint');

function wiz_add_custom_endpoint()
{
    add_rewrite_rule('^wiz-ajax/?', 'index.php?wiz_ajax_endpoint=1', 'top');
    add_rewrite_tag('%wiz_ajax_endpoint%', '1');
}