<?php
/**
 * Custom template loader for the Wizard plugin.
 * 
 * Handles template loading with theme override support following WordPress template hierarchy.
 * Templates can be overridden by placing them in: theme-directory/wizard/{template-path}
 * 
 * @since 1.0.0
 */
add_filter('template_include', function ($template) {
    // Define template mappings
    $template_mappings = [
        [
            'condition' => 'is_singular',
            'type' => 'wiz_template',
            'path' => 'single/single-wiz_template.php'
        ],
        [
            'condition' => 'is_post_type_archive',
            'type' => 'wiz_template',
            'path' => 'archive/archive-wiz_template.php'
        ],
        [
            'condition' => 'url_contains',
            'type' => '/account',
            'path' => 'account/page-account.php',
            'set_global' => 'wizard_account_page'
        ]
    ];

    /**
     * Locate template in theme or plugin directories.
     * 
     * @param string $template_path Relative path to the template
     * @return string|false Full path to the template or false if not found
     */
    $locate_template = function($template_path) {
        // Check theme directory first
        $theme_template = locate_template([
            'wizard/' . $template_path
        ]);
        
        if ($theme_template) {
            return $theme_template;
        }

        // Check plugin directory
        $plugin_template = plugin_dir_path(dirname(__FILE__)) . 'public/templates/' . $template_path;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    };

    /**
     * Check template and handle errors.
     * 
     * @param string $template_path Path to the template file
     * @return string Full path to the valid template
     */
    $check_template = function($template_path) use ($locate_template) {
        $full_path = $locate_template($template_path);
        
        if (!$full_path) {
            wp_die(sprintf(
                'Uh oh, spaghettios! 🍝<br><br>
                Template not found: <code>%s</code><br><br>
                To fix this, create the template in either:<br>
                - Your theme: <code>wp-content/themes/YOUR_THEME/wizard/%s</code><br>
                - Plugin: <code>wizard/public/templates/%s</code>',
                esc_html($template_path),
                esc_html($template_path),
                esc_html($template_path)
            ), 'Template Not Found 🔍');
        }
        
        if (filesize($full_path) == 0) {
            wp_die(sprintf(
                'This template file exists but is empty: <code>%s</code>',
                esc_html(basename($full_path))
            ), 'Empty Template File 📄');
        }
        
        return $full_path;
    };

    // Process template mappings
    foreach ($template_mappings as $mapping) {
        $match = false;

        switch ($mapping['condition']) {
            case 'is_singular':
                $match = is_singular($mapping['type']);
                break;
            case 'is_post_type_archive':
                $match = is_post_type_archive($mapping['type']);
                break;
            case 'url_contains':
                $match = strpos($_SERVER['REQUEST_URI'], $mapping['type']) !== false;
                break;
        }

        if ($match) {
            $new_template = $check_template($mapping['path']);
            
            // Set global variable if specified
            if (isset($mapping['set_global'])) {
                global ${$mapping['set_global']};
                ${$mapping['set_global']} = true;
            }
            
            return $new_template;
        }
    }

    return $template;
});