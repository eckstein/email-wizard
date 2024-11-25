<?php

// Register settings and options page
function wizard_settings_init() {
    // Register a new setting
    // register_setting('wizard_options', 'wizard_account_page_id');

    // Add a new section
    add_settings_section(
        'wizard_general_section',
        __('General Settings', 'wizard'),
        'wizard_general_section_callback',
        'wizard-options'
    );

    // Add fields to that section
    // add_settings_field(
    //     'wizard_account_page_id',
    //     __('Account Page', 'wizard'),
    //     'wizard_account_page_callback',
    //     'wizard-options',
    //     'wizard_general_section'
    // );

    
}
add_action('admin_init', 'wizard_settings_init');

// Add menu item
function wizard_options_page() {
    add_menu_page(
        'Wizard Options',
        'Wizard',
        'manage_options',
        'wizard-options',
        'wizard_render_options_page',
        'dashicons-email',
        6
    );
}
add_action('admin_menu', 'wizard_options_page');

// Section callback
function wizard_general_section_callback() {
    echo '<p>' . __('Configure your wizard settings below.', 'wizard') . '</p>';

}





// Render the options page
function wizard_render_options_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('wizard_options');
            do_settings_sections('wizard-options');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
