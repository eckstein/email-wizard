<?php
/**
 * Initialize Wizard Templates
 */
function wiz_templates_init() {
    
    // Initialize post type
    init_wizard_post_type();

    // Register template trash/restore hooks
    add_action('wp_trash_post', ['WizardTemplateManager', 'handle_template_trashed']);
    add_action('untrashed_post', ['WizardTemplateManager', 'handle_template_restored']);
}
add_action('init', 'wiz_templates_init'); 