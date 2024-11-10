<?php
add_action ('wiz_ajax_generate_template_table_part', 'ajax_generate_template_table_part');

function ajax_generate_template_table_part()
{
    $part = isset($_POST['part']) ? sanitize_text_field($_POST['part']) : '';
    $current_folder = isset($_POST['current_folder']) ? sanitize_text_field($_POST['current_folder']) : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $item_id = isset($_POST['item_id']) ? sanitize_text_field($_POST['item_id']) : null;
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $folder_ids = isset($_POST['folder_ids']) ? json_decode(stripslashes($_POST['folder_ids']), true) : [];
    $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : [];

    $args['search_term'] = $search_term;
    $args['folder_ids'] = $folder_ids;

    $result = generate_template_table_part($part, $current_folder, $user_id, $item_id, $args);

    if (isset($result['error'])) {
        wp_send_json_error($result['error']);
    } else {
        wp_send_json_success($result);
    }
}
function generate_template_table_part($part, $current_folder, $user_id, $item_id = null, $args = [])
{
    $validParts = ['folder_actions', 'header', 'subfolders', 'templates', 'folder_row', 'template_row', 'body', 'full'];
    if (!in_array($part, $validParts)) {
        return ['error' => 'Invalid part name passed'];
    }
    if (!$user_id || !$current_folder) {
        return ['error' => 'Missing user or folder id'];
    }

    $template_table = new WizardTemplateTable($user_id, $current_folder, $args);

    $html = $template_table->render($part, $item_id);
    return $html ? ['html' => $html] : ['error' => 'Failed to generate HTML'];
}

add_action('wiz_ajax_generate_wizard_folder_breadcrumb', 'ajax_generate_wizard_folder_breadcrumb');
function ajax_generate_wizard_folder_breadcrumb() {
    $user_id = get_current_user_id();
    $folderManager = new WizardFolders($user_id);
    $user_folders = $folderManager->get_folders();
    $current_folder = $_POST['current_folder'] ?? false;
    $breadcrumbHtml = generate_wizard_folder_breadcrumb($current_folder, $user_folders);
    if (!$breadcrumbHtml) {
        wp_send_json_error('Failed to generate breadcrumb HTML');
    }
    wp_send_json_success($breadcrumbHtml);
}
function generate_wizard_folder_breadcrumb($current_folder_id, $user_folders, $inline = false)
{

    $breadcrumbs = [];
    $folder_lookup = [];
    $templatesArchiveLink = get_post_type_archive_link('wiz_template');

    // Add persistent root folder item
    if ($current_folder_id === 'root' && !is_singular('wiz_template')) {
        $rootHtml = '<span class="breadcrumb-item root">All</span>';
    } else {
        $rootHtml = '<a class="breadcrumb-item root" href="' . esc_url($templatesArchiveLink) . '">All</a>';
    }

    // If current_folder_id = 'trash' we can just add a single, non-linked trash item and return
    if ($current_folder_id == 'trash') {
        $trashCrumb = '<div class="breadcrumb-wrapper"><div class="breadcrumb-inner">'.$rootHtml. '<i class="fa fa-chevron-right breadcrumb-separator"></i><span class="breadcrumb-item current"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;<span class="folder-title"  id="folder-trash" data-folder-id="trash">Trash</span></span></div></div>';
        return $trashCrumb;
    }

    // Create a lookup array to easily access folders by their ID
    foreach ($user_folders as $folder) {
        $folder_lookup[$folder['id']] = $folder;
    }

    // Start with the current folder and recursively add its ancestors to the breadcrumbs array
    while (isset($folder_lookup[$current_folder_id])) {
        $current_folder = $folder_lookup[$current_folder_id];
        array_unshift($breadcrumbs, $current_folder); // Prepend to keep the order from root to current
        $current_folder_id = $current_folder['parent_id'];
    }

    // Generate the HTML for the breadcrumbs
    $breadcrumbs_html = '<div class="breadcrumb-wrapper">';
    $breadcrumbs_html .= '<div class="breadcrumb-inner">';

    $breadcrumbs_html .= $rootHtml;

    foreach ($breadcrumbs as $index => $folder) {
        // Skip the root folder for breadcrumb display
        if ($folder['id'] === 'root')
            continue;

        // Separator with Font Awesome icon
        $breadcrumbs_html .= '<i class="fa fa-chevron-right breadcrumb-separator"></i>';

        // Make each folder a link, except the last one which is the current folder
        if ($index + 1 < count($breadcrumbs) || is_singular('wiz_template')) {
            $breadcrumbs_html .= '<a class="breadcrumb-item" href="' . esc_url(add_query_arg('folder_id', $folder['id'], $templatesArchiveLink)) . '"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;' . esc_html($folder['name']) . '</a>';
        } else {
            // if this is inline mode, we don't treat the last folder the same; as a link with no inline editing
            if ($inline) {
                $breadcrumbs_html .= '<a class="breadcrumb-item" href="' . esc_url(add_query_arg('folder_id', $folder['id'], $templatesArchiveLink)) . '"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;' . esc_html($folder['name']) . '</a>';
            } else {
                $breadcrumbs_html .= '<span class="breadcrumb-item current"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;<span class="editable folder-title"  id="' . $folder['id'] . '" data-folder-id="' . $folder['id'] . '">' . esc_html($folder['name']) . '</span><span class="dc-to-edit-message"><i class="fa-solid fa-pencil"></i></span></span>';
            }
        }
    }

    $breadcrumbs_html .= '</div>';
    $breadcrumbs_html .= '</div>';

    return $breadcrumbs_html;
}