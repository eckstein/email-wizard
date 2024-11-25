<?php
add_filter('template_include', function ($template) {
    // Define template mappings
    $template_mappings = [
        [
            'condition' => 'is_singular',
            'type' => 'wiz_template',
            'path' => 'public/templates/single-template/single-wiz_template.php'
        ],
        [
            'condition' => 'is_post_type_archive',
            'type' => 'wiz_template',
            'path' => 'public/templates/template-archive/archive-wiz_template.php'
        ],
        [
            'condition' => 'url_contains',
            'type' => '/account',
            'path' => 'public/templates/account/page-account.php',
            'set_global' => 'wizard_account_page'
        ],
        [
            'condition' => 'url_contains',
            'type' => '/team-settings',
            'path' => 'public/templates/teams/page-team-settings.php',
            'set_global' => 'wizard_team_settings_page'
        ]
    ];

    // Helper function to check template and handle errors
    $check_template = function($path) {
        $full_path = plugin_dir_path(dirname(__FILE__)) . $path;
        
        if (!file_exists($full_path)) {
            wp_die('Template file does not exist: ' . $full_path);
        }
        
        if (filesize($full_path) == 0) {
            wp_die('Uh oh, Spaghettios! Content for this template is missing.');
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