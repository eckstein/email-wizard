<?php

// Handle template archive and search redirects
function wizard_template_search_redirect() {
    if (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'wiz_template') {
        $archive_url = get_post_type_archive_link('wiz_template');
        $search_query = get_search_query();
        $redirect_url = add_query_arg([
            's' => $search_query,
            'post_type' => 'wiz_template'
        ], $archive_url);
        
        // Preserve other query parameters
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['s', 'post_type'])) {
                $redirect_url = add_query_arg($key, $value, $redirect_url);
            }
        }

        wp_redirect($redirect_url);
        exit;
    }
}

// Handle folder redirects
function wizard_folder_redirects() {
    if (is_archive() && isset($_GET['folder_id'])) {
        $folder_id = $_GET['folder_id'];
        $user_id = get_current_user_id();
        
        $folderManager = new WizardFolders($user_id);
        
        $folder_exists = ($folder_id === 'trash') ? true : ($folderManager->get_folder($folder_id) !== null);

        if (!$folder_exists) {
            $allTemplatesArchive = get_post_type_archive_link('wiz_template');
            if ($allTemplatesArchive) {
                wp_redirect($allTemplatesArchive);
                exit;
            }
        }
    }
}

// Check for invalid pagination
function wizard_check_invalid_pagination_redirect() {
    if (is_post_type_archive('wiz_template')) {
        $current_page = get_query_var('paged') ? get_query_var('paged') : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $folder_id = isset($_GET['folder_id']) ? $_GET['folder_id'] : 'root';

        // Only check if we're on page 2 or higher
        if ($current_page > 1) {
            $user_id = get_current_user_id();
            $teams = new WizardTeams();
            $active_team = $teams->get_active_team($user_id);

            // Set up query args for counting templates
            $count_args = array(
                'post_type' => 'wiz_template',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => 'wizard_team',
                        'value' => $active_team
                    )
                )
            );

            // Add folder-specific conditions
            if ($folder_id === 'trash') {
                $count_args['post_status'] = 'trash';
            } else {
                $count_args['post_status'] = 'publish';
                if ($folder_id !== 'root') {
                    $count_args['meta_query'][] = array(
                        'key' => 'wizard_folder',
                        'value' => $folder_id
                    );
                }
            }

            // Add search condition if searching
            if (get_search_query()) {
                $count_args['s'] = get_search_query();

                // If searching in a normal folder, include subfolders
                if ($folder_id !== 'trash' && $folder_id !== 'root') {
                    $folders = new WizardFolders($user_id, $active_team);
                    $subfolder_ids = $folders->get_subfolder_ids($folder_id, true);
                    $folder_ids = array_merge([$folder_id], $subfolder_ids);

                    $count_args['meta_query'][] = array(
                        'key' => 'wizard_folder',
                        'value' => $folder_ids,
                        'compare' => 'IN'
                    );
                }
            }

            // Count total templates
            $template_query = new WP_Query($count_args);
            $total_items = $template_query->found_posts;
            $max_pages = ceil($total_items / $per_page);

            // If current page is beyond max pages, redirect to last valid page
            if ($current_page > $max_pages) {
                $redirect_args = $_GET;
                if ($max_pages > 0) {
                    $redirect_args['paged'] = $max_pages;
                } else {
                    unset($redirect_args['paged']);
                }

                $redirect_url = add_query_arg(
                    $redirect_args,
                    get_post_type_archive_link('wiz_template')
                );

                wp_redirect($redirect_url);
                exit;
            }
        }
    }
}

// Template search template filter
function wizard_template_search_template($template) {
    if (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'wiz_template') {
        return get_archive_template();
    }
    return $template;
}
add_filter('template_include', 'wizard_template_search_template');
