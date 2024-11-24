<?php

/**
 * Register template trash/restore hooks
 */
function register_template_trash_hooks()
{
    add_action('wp_trash_post', 'handle_template_trashed');
    add_action('untrashed_post', 'handle_template_restored');
}
add_action('init', 'register_template_trash_hooks');

if (!function_exists('handle_template_trashed')) {
    function handle_template_trashed($post_id)
    {
        // Only proceed if this is a template post type
        if (get_post_type($post_id) !== 'wiz_template') {
            return;
        }

        $wizard = new WizardTemplates();
        $wizard->handle_template_trashed($post_id);
    }
}

if (!function_exists('handle_template_restored')) {
    function handle_template_restored($post_id)
    {
        // Only proceed if this is a template post type
        if (get_post_type($post_id) !== 'wiz_template') {
            return;
        }

        $wizard = new WizardTemplates();
        $wizard->handle_template_restored($post_id);
    }
}

// Remove any existing hooks first to prevent duplicates
remove_action('wp_trash_post', ['WizardTemplates', 'handle_template_trashed']);
remove_action('untrashed_post', ['WizardTemplates', 'handle_template_restored']);

// Add our hooks using the static class methods directly
add_action('wp_trash_post', ['WizardTemplates', 'handle_template_trashed']);
add_action('untrashed_post', ['WizardTemplates', 'handle_template_restored']);
