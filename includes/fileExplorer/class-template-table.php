<?php
class WizardTemplateTable
{
    // Establish variables
    private $folder_id;
    private $team_id;
    private $user_folders;
    private $folder_templates;
    private $template_archive_link;
    private $subfolder_ids;
    private $search_term;
    private $search_folder_ids;
    private $args;

    public function __construct($current_user_id, $folder_id, $args = [])
    {
        $user_team = get_user_meta($current_user_id, 'wizard_teams')[0] ?? null;
        $this->user_folders = new WizardFolders($current_user_id, $user_team);
        $this->folder_id = $folder_id;
        $this->team_id = $user_team;
        $this->template_archive_link = get_post_type_archive_link('wiz_template');

        if ($folder_id === 'trash') {
            $this->folder_templates = $this->get_trashed_templates();
        } else {
            $this->folder_templates = $this->get_templates_in_folders([$folder_id]);
            $this->subfolder_ids = $this->user_folders->get_subfolder_ids($folder_id, false);
        }

        $this->search_term = $args['search_term'] ?? '';
        $this->search_folder_ids = $args['folder_ids'] ?? [$folder_id];
        $this->args = $args;

        if (!empty($this->search_term)) {
            $this->folder_templates = $this->get_templates_in_folders($this->search_folder_ids);
        }
    }

    /**
     * Renders all or part of the template table based on the requested part.
     *
     * @param string $part The part of the table to render. Can be 'full', 'folder_actions', 'header', 'subfolders', 'templates', 'body', 'folder_row', or 'template_row'.
     * @param int|null $id The ID of the folder or template to render a row for.
     * @return string The rendered HTML for the requested part of the table.
     */
    public function render($part = 'full', $itemId = null, $showRowBreadcrumb = false)
    {
        $folder_id = $this->folder_id;
        $folder_subfolders = $this->subfolder_ids;
        $user_folders = $this->user_folders;
        switch ($part) {
            case 'folder_actions':
                return $this->render_folder_actions($folder_id);
            case 'folder_navigation':
                return $this->render_folder_navigation($folder_id, $user_folders);
            case 'header':
                return $this->render_table_header($folder_id, $this->args);
            case 'subfolders':
                return $this->render_subfolders();
            case 'templates':
                return $this->render_templates($folder_id, count($folder_subfolders));
            case 'body':
                return $this->render_table_body();
            case 'folder_row':
                return $itemId ? $this->render_folder_row($itemId) : '';
            case 'template_row':
                return $itemId ? $this->render_template_row($itemId, $folder_id, $showRowBreadcrumb) : '';
            case 'full':
            default:
                return $this->render_full_table();
        }
    }
      private function get_templates_in_folders($folder_ids)
      {
          $templates = [];
          foreach ($folder_ids as $folder_id) {
              $folder_templates = $this->user_folders->get_templates_in_folder($folder_id);
              if (!empty($folder_templates)) {
                  $templates = array_merge($templates, $folder_templates);
              }
          }
          return $templates;
      }
    private function get_trashed_templates()
    {
        $args = array(
            'post_type' => 'wiz_template',
            'post_status' => 'trash',
            'numberposts' => -1
        );
        return get_posts($args);
    }

    private function render_full_table()
    {
        ob_start();
?>


        <table class="wizard-folders-table">
            <?php
            echo $this->render_folder_navigation($this->folder_id, $this->user_folders);
            echo $this->render_table_header($this->folder_id, $this->args);
            echo $this->render_table_body();
            ?>
        </table>
    <?php
        return ob_get_clean();
    }

    public function generate_folder_breadcrumb($current_folder_id, $inline = false)
    {
        $templatesArchiveLink = get_post_type_archive_link('wiz_template');

        $rootHtml = ($current_folder_id === 'root' && !is_singular('wiz_template'))
        ? '<span class="breadcrumb-item root">All</span>'
        : '<a class="breadcrumb-item root" href="' . esc_url($templatesArchiveLink) . '">All</a>';

        if ($current_folder_id == 'trash'
        ) {
            return '<div class="breadcrumb-wrapper"><div class="breadcrumb-inner">' . $rootHtml .
            '<i class="fa fa-chevron-right breadcrumb-separator"></i>' .
            '<span class="breadcrumb-item current"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;' .
            '<span class="folder-title" id="folder-trash" data-folder-id="trash">Trash</span></span></div></div>';
        }

        $folder_path = $this->user_folders->get_folder_path($current_folder_id);

        $breadcrumbs_html = '<div class="breadcrumb-wrapper"><div class="breadcrumb-inner">' . $rootHtml;

        foreach ($folder_path as $index => $folder) {
            $breadcrumbs_html .= '<i class="fa fa-chevron-right breadcrumb-separator"></i>';

            $is_last = ($index === count($folder_path) - 1);
            $folder_link = esc_url(add_query_arg('folder_id', $folder['id'], $templatesArchiveLink));

            if (!$is_last || is_singular('wiz_template') || $inline) {
                $breadcrumbs_html .= '<a class="breadcrumb-item" href="' . $folder_link . '">' .
                    '<i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;' .
                    esc_html($folder['name']) . '</a>';
            } else {
                $breadcrumbs_html .= '<span class="breadcrumb-item current">' .
                    '<i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;' .
                    '<span class="editable folder-title" id="' . $folder['id'] . '" data-folder-id="' . $folder['id'] . '">' .
                    esc_html($folder['name']) . '</span>' .
                    '<span class="dc-to-edit-message"><i class="fa-solid fa-pencil"></i></span></span>';
            }
        }

        $breadcrumbs_html .= '</div></div>';

        return $breadcrumbs_html;
    }

    private function render_folder_actions($folder_id)
    {
        ob_start();
        $current_folder = $folder_id ?? 'root';
    ?>
        <div id="template-search-active"><?php echo $current_folder == 'trash' ? 'Showing: Trashed templates' : ''; ?></div>
        <div class="folder-actions">



            <div id="folder-tasks">
                <?php if ($current_folder !== 'trash') { ?>
                    <button class="wizard-button small create-folder" data-folder-id="<?php echo $folder_id; ?>"><i
                            class="fa-solid fa-folder-plus"></i>&nbsp;&nbsp;New Folder</button>
                <?php
                }
                if ($current_folder !== 'root' && $current_folder !== 'trash') { ?>
                    <button class="wizard-button small move-folder" data-folder-id="<?php echo $folder_id; ?>" title="Move folder"><i
                            class="fa-solid fa-folder-tree"></i></button>

                    <button class="wizard-button small red delete-folder" data-folder-id="<?php echo $folder_id; ?>" title="Delete folder"><i
                            class="fa-solid fa-trash"></i></button>
                <?php } ?>
            </div>
            <?php if ($current_folder !== 'trash') { ?>
                <div id="template-search" class="wizard-form-fieldgroup template-search"><input type="search" class="wizard-search" placeholder="Search folder..." />

                </div>
            <?php } ?>
        </div>
    <?php
        return ob_get_clean();
    }

    private function render_folder_navigation($folder_id, $user_folders)
    {
        ob_start();
    ?>
        <div id="user-folder-top-bar">
            <div id="user-folder-breadcrumb">
                <?php
                $breadcrumb = $this->generate_folder_breadcrumb($folder_id);
                echo '<h1>Templates</h1> | ' . $breadcrumb;
                ?>
            </div>
            <?php echo $this->render_folder_actions($this->folder_id); ?>

        </div>
        <!-- <a href="<?php //echo add_query_arg('folder_id', 'trash', $this->template_archive_link); 
                        ?>" class="view-trash-link">View trash</a> -->
    <?php
        return ob_get_clean();
    }

    private function render_bulk_actions_menu($folder_id)
    {
        ob_start();
    ?>
        <div class="bulk-check-actions-menu wizard-dropdown disabled" id="bulk-actions">
            <i class="fa-solid fa-angle-down" title="Bulk actions"></i>
            <div class="wizard-dropdown-panel">
                <ul class="wizard-dropdown-menu">
                    <?php if ($folder_id === 'trash'): ?>
                        <li class="bulk-action-item dropdown-action" id="restore-selected" disabled><i class="fa-solid fa-trash-restore"></i>&nbsp;&nbsp;Restore</li>
                        <li class="bulk-action-item dropdown-action" id="delete-selected-forever" disabled><i class="fa-solid fa-trash-alt"></i>&nbsp;&nbsp;Delete Forever</li>
                    <?php else: ?>
                        <li class="bulk-action-item dropdown-action" id="move-selected" disabled><i
                                class="fa-solid fa-folder-tree"></i>&nbsp;&nbsp;Move</li>
                        <li class="bulk-action-item dropdown-action" id="delete-selected" disabled><i
                                class="fa-solid fa-trash"></i>&nbsp;&nbsp;Delete</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    private function render_table_header($folder_id, $args)
    {
        if (isset($args['sortBy']) && isset($args['sort'])) {

            $nameActive = $args['sortBy'] === 'name' ? 'active' : '';
            $modifiedActive = $args['sortBy'] === 'last_updated' ? 'active' : '';
            $currentSort = $args['sort'] === 'name' ? 'ASC' : 'DESC';

            if ($args['sortBy'] === 'name') {
                $nameIcon = $currentSort === 'ASC' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down';
                $modifiedIcon = 'fa-solid fa-sort';
            } elseif ($args['sortBy'] === 'last_updated') {
                $nameIcon = 'fa-solid fa-sort';
                $modifiedIcon = $currentSort === 'ASC' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down';
            }
        } else {
            $nameActive = '';
            $modifiedActive = '';
            $nameIcon = 'fa-solid fa-sort';
            $modifiedIcon = 'fa-solid fa-sort';
        }

        ob_start();
    ?>
        <thead>
            <tr>
                <th class="wizard-table-bulk-check-all header"><input type="checkbox"></th>
                <th class="wizard-table-bulk-actions header">


                    <?php echo $this->render_bulk_actions_menu($folder_id); ?>


                </th>
                <th class="wizard-table-template-name wizard-table-col-header sortable" data-sorted="false" data-sort="ASC" data-sort-by="name">
                    <div>
                        <span class="col-header-label">Name</span>
                        <span class="sorting-indicator <?php echo $nameActive; ?>"><i class="<?php echo $nameIcon; ?>"></i></span>
                    </div>
                </th>
                <th class="wizard-table-last-modified wizard-table-col-header sortable" data-sorted="true" data-sort="DESC" data-sort-by="last_updated">
                    <div>
                        <span class="col-header-label">Modified</span>
                        <span class="sorting-indicator <?php echo $modifiedActive; ?>"><i class="<?php echo $modifiedIcon; ?>"></i></span>
                    </div>
                </th>
                <th class="wizard-table-template-actions header">Actions</th>
            </tr>
        </thead>
    <?php
        return ob_get_clean();
    }
    private function render_table_body()
    {
        $subfolders = $this->user_folders->get_subfolder_ids($this->folder_id);
        $subfolders_count = count($subfolders);
        ob_start();
    ?>
        <tbody class="subfolders list">
            <?php echo $this->render_subfolders(); ?>
        </tbody>
        <tbody class="templates list">
            <?php echo $this->render_templates($this->folder_id, $subfolders_count); ?>

        </tbody>
<?php
        return ob_get_clean();
    }

    private function render_subfolders()
    {
        if ($this->folder_id === 'trash'
        ) {
            return '';
        }
        $subfolders = $this->user_folders->get_subfolders($this->folder_id, false);
        usort($subfolders, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        ob_start();
        foreach ($subfolders as $subfolder) {
            if ($this->render_folder_row($subfolder['id'])) {
                echo $this->render_folder_row($subfolder['id']);
            }
        }
        return ob_get_clean();
    }
    private function render_templates($folder_id, $subfolders_count)
    {
        // Sort the templates
        $sortBy = $this->args['sortBy'] ?? 'last_updated';
        $sort = $this->args['sort'] ?? 'DESC';

        if ($folder_id !== 'trash') {
            // Prepare the data for sorting
            $sortedTemplates = array_map(function ($template) {
                $templateManager = new WizardTemplates();
                $wizTemplate = $templateManager->get_template($template->ID);
                return [
                    'original' => $template,
                    'post' => $template,  // This is the WordPress post object
                    'wizData' => $wizTemplate  // This is the custom wiztemplate data
                ];
            }, $this->folder_templates);

            // Define the sorting function
            $sortFunction = function ($a, $b) use ($sortBy, $sort) {
                switch ($sortBy) {
                    case 'name':
                        $compareResult = strcmp($a['post']->post_title ?? '', $b['post']->post_title ?? '');
                        break;
                    case 'last_modified':
                        $timeA = isset($a['post']->post_modified) ? strtotime($a['post']->post_modified) : 0;
                        $timeB = isset($b['post']->post_modified) ? strtotime($b['post']->post_modified) : 0;
                        $compareResult = $timeA - $timeB;
                        break;
                    case 'last_updated':
                        $timeA = isset($a['wizData']['last_updated']) ? strtotime($a['wizData']['last_updated']) : 0;
                        $timeB = isset($b['wizData']['last_updated']) ? strtotime($b['wizData']['last_updated']) : 0;
                        $compareResult = $timeA - $timeB;
                        break;
                    default:
                        $compareResult = 0;
                        break;
                }
                return ($sort === 'ASC') ? $compareResult : -$compareResult;
            };

            // Sort the array
            usort($sortedTemplates, $sortFunction);

            // Extract the sorted original templates
            $this->folder_templates = array_column($sortedTemplates, 'original');
        }

        // Filter the sorted templates
        $filtered_templates = array_filter($this->folder_templates, function ($template) {
            return empty($this->search_term) ||
                stripos($template->post_title, $this->search_term) !== false;
        });

        ob_start();
        if (empty($filtered_templates)) {
            if (
                $subfolders_count === 0
            ) {
                echo '<tr><td></td><td></td><td colspan="3" class="no-results-message">No templates here!</td></tr>';
            }
        } else {
            foreach ($filtered_templates as $template) {
                echo $this->render_template_row($template->ID, $folder_id, $this->args['show_row_breadcrumb'] ?? false);
            }
        }
        return ob_get_clean();
    }

    private function render_folder_row($folder_id)
    {
        $subfolder = $this->user_folders->get_folder($folder_id);

        // get count of subfolders in this folder
        $subfolder_count = count($this->user_folders->get_subfolder_ids($folder_id));

        // find templates that have this folder in the user_folders post meta
        $folder_templates = $this->user_folders->get_templates_in_folder($folder_id);
        $template_count = count($folder_templates);

        if (!$subfolder) {
            return '';
        }

        ob_start();
        $folderPermalink = esc_url(add_query_arg('folder_id', $subfolder['id'], $this->template_archive_link));
        echo '<tr data-type="folder" id="folder-' . $subfolder['id'] . '" data-id="' . $subfolder['id'] . '">';
        echo '<td class="wizard-table-bulk-check folder"><input type="checkbox" name="' . $subfolder['id'] . '_checkbox" value="' . $subfolder['id'] . '" data-type="folder"></td>';
        echo '<td class="wizard-table-icon folder"><a class="wizard-table-td-link" title="' . esc_html($subfolder['name']) . '" href="' . $folderPermalink . '"></a><i class="fa-solid fa-folder"></i></td>';
        echo '<td class="wizard-table-template-name" colspan="2"><a class="wizard-table-td-link " title="' . esc_html($subfolder['name']) . '" href="' . $folderPermalink . '"></a><a class="wizard-table-folder-name-link" href="' . $folderPermalink . '">' . esc_html($subfolder['name']) . '</a><span class="wizard-template-table-indicators"><span><i class="fa-solid fa-folder"></i>&nbsp;&nbsp;' . $subfolder_count . '</span><span><i class="fa-solid fa-file-code"></i>&nbsp;&nbsp;' . $template_count . '</span></span></td>';
        echo '<td class="wizard-table-template-actions"><div class="table-template-actions-inner">';
        echo '<i title="Edit folder title" class="fa-solid fa-pencil edit-folder-title" data-editable="' . $subfolder['id'] . '"></i>
        <i title="Move Folder" class="fa-solid fa-folder-tree move-folder" data-folder-id="' . $subfolder['id'] . '"></i>
        <i title="Delete Folder" class="fa-solid fa-trash delete-folder" data-folder-id="' . $subfolder['id'] . '"></i></div></td>';
        echo '</tr>';
        return ob_get_clean();
    }

    private function render_template_row($template_id, $folder_id, $breadcrumb = false)
    {
        $template = array_filter($this->folder_templates, function ($t) use ($template_id) {
            return $t->ID == $template_id;
        });
        $template = reset($template);

        if (!$template) {
            return '';
        }

        $templateManager = new WizardTemplates();
        $wizTemplate = $templateManager->get_template($template->ID);
        $lastUpdated = 0;
        if (isset($wizTemplate['last_updated'])) {
            $lastUpdated = new DateTime($wizTemplate['last_updated']);
            $lastUpdated = $lastUpdated->format('m/j/t\, g:i A');
        }
        $thisTemplatesFolder = get_post_meta($template->ID, 'wizard_folder', true);

        // Check if this folder is a child of the current folder
        $isChildOfCurrentFolder = $folder_id == $thisTemplatesFolder;

        $breadcrumbHtml = $this->generate_folder_breadcrumb($thisTemplatesFolder, true);
        if ($breadcrumb) {
            $maybeBreadcrumb = '<div class="template-row-breadcrumb">';
            if ($isChildOfCurrentFolder) {
                $maybeBreadcrumb .= "<span class='breadcrumb-item'>Current folder</span>";
            } else {
                $maybeBreadcrumb .= $breadcrumbHtml;
            }
            $maybeBreadcrumb .= '</div>';
        } else {
            $maybeBreadcrumb = '';
        }

        ob_start();
        $templatePermalink = get_the_permalink($template->ID);
        echo '<tr data-type="template" id="template-' . $template->ID . '" data-id="' . $template->ID . '">';
        echo '<td class="wizard-table-bulk-check template"><input type="checkbox" name="' . $template->ID . '_checkbox"value="' . $template->ID . '" data-type="template"></td>';
        echo '<td class="wizard-table-icon template"><i class="fa-solid fa-file-code"></i></td>';
        echo '<td class="wizard-table-template-name"><a class="wizard-table-template-name-link" href="' . $templatePermalink . '">' . $template->post_title . '</a>' . $maybeBreadcrumb . '</td>';
        echo '<td class="wizard-table-last-modified">' . ($lastUpdated > 0 ? $lastUpdated : "n/a") . '</td>';
        echo '<td class="wizard-table-template-actions"><div class="table-template-actions-inner">';
        if ($folder_id === 'trash') {
            echo '<i title="Restore Template" class="fa-solid fa-trash-restore restore-template" data-type="template" data-template-id="' . $template->ID . '"></i>
                  <i title="Delete Permanently" class="fa-solid fa-trash-alt delete-forever" data-type="template" data-template-id="' . $template->ID . '"></i>';
        } else {
            echo '<i title="Duplicate Template" class="fa-solid fa-copy duplicate-template" data-template-id="' . $template->ID . '"></i>
        <i title="Move Template" class="fa-solid fa-folder-tree move-template" data-template-id="' . $template->ID . '"></i>
        <i title="Trash Template" class="fa-solid fa-trash delete-template" data-template-id="' . $template->ID . '"></i></div></td>';
        }
        echo '</tr>';
        return ob_get_clean();
    }

    
}
