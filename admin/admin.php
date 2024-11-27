<?php

// Add this near the top of the file with other add_action calls
add_action('admin_enqueue_scripts', 'wizard_admin_scripts');

// Add this function to enqueue the media library scripts
function wizard_admin_scripts($hook) {
    // Only load on our settings page
    if ($hook != 'toplevel_page_wizard-options') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery');
}

// Register settings and options page
function wizard_settings_init() {
    // Register settings
    register_setting('wizard_options', 'wizard_default_avatar', [
        'type' => 'integer',
        'description' => 'Default avatar attachment ID',
        'sanitize_callback' => 'absint',
        'default' => 0
    ]);

    // Add settings section
    add_settings_section(
        'wizard_general_section',
        __('General Settings', 'wizard'),
        'wizard_general_section_callback',
        'wizard-options'
    );

    // Add avatar field
    add_settings_field(
        'wizard_default_avatar',
        __('Default Avatar', 'wizard'),
        'wizard_default_avatar_callback',
        'wizard-options',
        'wizard_general_section'
    );
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

// Avatar field callback
function wizard_default_avatar_callback() {
    $avatar_id = get_option('wizard_default_avatar');
    ?>
    <div class="default-avatar-setting">
        <div class="avatar-preview" style="margin-bottom: 10px;">
            <?php 
            if ($avatar_id) {
                echo wp_get_attachment_image($avatar_id, [96, 96], false, ['style' => 'border-radius: 50%;']);
            }
            ?>
        </div>
        <input type="hidden" name="wizard_default_avatar" id="wizard_default_avatar" value="<?php echo esc_attr($avatar_id); ?>">
        <button type="button" class="button select-avatar-button">
            <?php echo $avatar_id ? __('Change Avatar', 'wizard') : __('Select Avatar', 'wizard'); ?>
        </button>
        <?php if ($avatar_id): ?>
            <button type="button" class="button remove-avatar-button">
                <?php _e('Remove', 'wizard'); ?>
            </button>
        <?php endif; ?>
        <p class="description">
            <?php _e('This avatar will be used as the default for new users and teams.', 'wizard'); ?>
        </p>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var frame;
        $('.select-avatar-button').on('click', function(e) {
            e.preventDefault();

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: '<?php _e('Select Default Avatar', 'wizard'); ?>',
                button: {
                    text: '<?php _e('Use as default avatar', 'wizard'); ?>'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#wizard_default_avatar').val(attachment.id);
                $('.avatar-preview').html('<img src="' + attachment.url + '" style="width: 96px; height: 96px; border-radius: 50%;">');
                $('.remove-avatar-button').show();
            });

            frame.open();
        });

        $('.remove-avatar-button').on('click', function(e) {
            e.preventDefault();
            $('#wizard_default_avatar').val('');
            $('.avatar-preview').empty();
            $(this).hide();
        });
    });
    </script>
    <?php
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
