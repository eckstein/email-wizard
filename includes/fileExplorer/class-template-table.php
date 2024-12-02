<?php
class WizardTemplateTable
{
    // Establish variables
    private $folder_id;
    private $user_id;
    private $team_id;
    private $user_folders;
    private $template_archive_link;
    private $subfolder_ids;
    private $args;
    private $template_manager;

    public function __construct($current_user_id, $folder_id, $args = [])
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return;
        }

        $this->user_id = $current_user_id ?? get_current_user_id();
        $teams = new WizardTeams();
        $active_team = $teams->get_active_team($this->user_id);
        
        // If no active team, initialize with empty values
        if (!$active_team) {
            $this->user_folders = null;
            $this->folder_id = null;
            $this->team_id = null;
            return;
        }

        $this->user_folders = new WizardFolders($this->user_id, $active_team);
        
        $this->folder_id = $folder_id;
        $this->team_id = $active_team;
        $this->template_archive_link = get_post_type_archive_link('wiz_template');

        // Make sure we're getting the page and per_page from URL if not in args
        if (!isset($args['page']) && isset($_GET['page'])) {
            $args['page'] = (int)$_GET['page'];
        }
        if (!isset($args['per_page']) && isset($_GET['per_page'])) {
            $args['per_page'] = (int)$_GET['per_page'];
        }
        // Set default per_page if not specified
        if (!isset($args['per_page'])) {
            $args['per_page'] = 10;
        }

        $this->args = $args;
        $this->template_manager = new WizardTemplateManager();

        add_action('pre_get_posts', array($this, 'modify_template_query'));
    }

    public function modify_template_query($query)
    {

        if (!is_admin() && $query->is_post_type_archive('wiz_template')) {

            // Get per_page from URL or default to 10
            $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

            // Get sort parameters
            $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'last_updated';
            $order = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';
            
            $sort = $this->get_sort_parameters(['orderby' => $orderby, 'order' => $order]);

            // Set basic query parameters
            $query->set('post_type', 'wiz_template');
            $query->set('posts_per_page', $per_page);
            $query->set('orderby', $sort['wpOrderby']);
            $query->set('order', $sort['order']);

            // Get all subfolder IDs if searching
            $folder_ids = [$this->folder_id];
            if (get_search_query() && $this->folder_id !== 'trash') {
                $recursive_subfolders = $this->user_folders->get_subfolder_ids($this->folder_id, true);
                $folder_ids = array_merge($folder_ids, $recursive_subfolders);
            }

            // Add team meta query
            $meta_query = [
                [
                    'key' => 'wizard_team',
                    'value' => $this->team_id
                ]
            ];

            // Add folder meta query if not in trash
            if ($this->folder_id !== 'trash') {
                $meta_query[] = [
                    'key' => 'wizard_folder',
                    'value' => $folder_ids,
                    'compare' => 'IN'
                ];
            }

            // Set the complete meta query
            $query->set('meta_query', [
                'relation' => 'AND',
                ...$meta_query
            ]);

            // Set post status based on folder
            $query->set('post_status', $this->folder_id === 'trash' ? 'trash' : 'publish');

            // Add search functionality
            if (get_search_query()) {
                add_filter('posts_search', [$this, 'customize_search_query'], 10, 2);
            }
        }
    }

    /**
     * Customizes the search query to include template metadata
     */
    public function customize_search_query($search, $query)
    {
        global $wpdb;

        if (!$query->is_search() || !$query->is_main_query()) {
            return $search;
        }

        $search_term = get_search_query();
        if (empty($search_term)) {
            return $search;
        }

        // Build custom search query
        $search = " AND (
            {$wpdb->posts}.post_title LIKE '%" . esc_sql($search_term) . "%'
            OR {$wpdb->posts}.post_content LIKE '%" . esc_sql($search_term) . "%'
            OR EXISTS (
                SELECT 1 
                FROM {$wpdb->postmeta} 
                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                AND {$wpdb->postmeta}.meta_value LIKE '%" . esc_sql($search_term) . "%'
            )
        )";

        return $search;
    }

    /**
     * Updates the search form to maintain folder context
     */
    private function render_search_form()
    {
        $search_query = get_search_query();
        $current_url = get_post_type_archive_link('wiz_template');

        // Preserve existing query parameters except 'page' and 's'
        $existing_params = $_GET;
        unset($existing_params['page'], $existing_params['s']);

        ob_start();
?>

        <div class="wizard-search-wrapper">
            <form role="search" method="get" class="wizard-search-form" action="<?php echo esc_url($current_url); ?>">
                <?php
                // Add hidden inputs for existing parameters
                foreach ($existing_params as $key => $value): ?>
                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                <?php endforeach; ?>

                <div class="wizard-search-input-wrapper wizard-form-fieldgroup">
                    <input type="search"
                        name="s"
                        value="<?php echo esc_attr($search_query); ?>"
                        placeholder="<?php esc_attr_e('Search this folder...', 'wizard'); ?>"
                        class="wizard-search-input">
                    <button type="submit" class="wizard-button wizard-search-submit">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </div>
            </form>


        </div>
    <?php
        return ob_get_clean();
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
        // Check if user is logged in and has an active team
        if (!is_user_logged_in() || !$this->team_id) {
            return '';
        }

        $folder_id = $this->folder_id;
        $folder_subfolders = $this->subfolder_ids ?? [];
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
    private function get_templates($folder_id, $args = [])
    {
        $query_args = $this->build_template_query_args($folder_id, $args);
        $query = new WP_Query($query_args);

        return [
            'templates' => array_map([$this->template_manager, 'enrich_template_post'], $query->posts),
            'total' => $query->found_posts,
            'max_pages' => $query->max_num_pages
        ];
    }
    private function get_sort_parameters($args = [])
    {
        $orderby = $args['orderby'] ?? 'last_updated';
        $order = $args['order'] ?? 'DESC';

        $orderby_map = [
            'name' => 'title',
            'last_updated' => 'post_modified'
        ];

        return [
            'wpOrderby' => $orderby_map[$orderby] ?? 'post_modified',
            'order' => $order,
            'originalOrderby' => $orderby // Keep original for UI purposes
        ];
    }
    private function build_template_query_args($folder_id, $args = [])
    {
        $sort = $this->get_sort_parameters($args);

        // Use WordPress' paged parameter if available
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : (isset($args['per_page']) ? (int)$args['per_page'] : 10);

        // Base query args
        $query_args = [
            'post_type' => 'wiz_template',
            'post_status' => $folder_id === 'trash' ? 'trash' : 'publish',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'orderby' => $sort['wpOrderby'],
            'order' => $sort['order'],
            'meta_query' => [
                [
                    'key' => 'wizard_team',
                    'value' => $this->team_id
                ]
            ]
        ];

        // Only add folder meta query if not in trash
        if ($folder_id !== 'trash') {
            // Only use multiple folder IDs if we're searching
            $folder_ids = !empty($args['search_term']) ?
                ($args['folder_ids'] ?? [$folder_id]) :
                [$folder_id];

            $query_args['meta_query'][] = [
                'key' => 'wizard_folder',
                'value' => $folder_ids,
                'compare' => 'IN'
            ];
        }

        // Handle search
        $search_term = get_search_query();
        if (!empty($search_term)) {
            $query_args['s'] = $search_term;
        }

        return $query_args;
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

        if (
            $current_folder_id == 'trash'
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

    private function render_folder_actions()
    {
        ob_start();
    ?>
        <?php
        $search_query = get_search_query();
        if ($search_query): ?>
            <div id="template-search-active" class="template-search-status">
                <?php if (have_posts()): ?>
                    <span class="search-status-text">
                        <?php echo esc_html('Showing results for: '); ?>
                        <strong><?php echo esc_html($search_query); ?></strong>
                    </span>
                <?php else: ?>
                    <span class="search-status-text">
                        <?php echo esc_html('No results for: '); ?>
                        <strong><?php echo esc_html($search_query); ?></strong>
                    </span>
                <?php endif; ?>
                <a href="<?php echo esc_url(remove_query_arg('s')); ?>" class="clear-search">
                    (clear search)
                </a>
            </div>
        <?php endif; ?>
        <div class="wizard-folder-actions">



            <?php echo $this->render_search_form(); ?>
            <div class="wizard-folder-actions-right">
                <!-- <button class="wizard-button new-template">
                    <i class="fa-solid fa-plus"></i> New Template
                </button> -->
                <button class="wizard-button create-folder">
                    <i class="fa-solid fa-folder-plus"></i> New Folder
                </button>
            </div>
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

    private function render_table_header()
    {
        // Get current sort parameters
        $current_orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'last_updated';
        $current_order = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';

        // Define sortable columns and their defaults
        $sortable_columns = [
            'name' => [
                'label' => 'Name',
                'default_order' => 'ASC'
            ],
            'last_updated' => [
                'label' => 'Last Updated',
                'default_order' => 'DESC'
            ]
        ];

        ob_start();
    ?>
        <thead>
        <tr class="wizard-table-header">
            <th class="wizard-table-bulk-check">
                <input type="checkbox" class="wizard-table-bulk-check-all">

            </th>
            <th class="wizard-table-icon"><?php echo $this->render_bulk_actions_menu($this->folder_id); ?></th>
            <?php foreach ($sortable_columns as $column_key => $column):
                // Determine sort order for this column
                $is_current = ($current_orderby === $column_key);
                $next_order = $is_current && $current_order === 'ASC' ? 'DESC' : 'ASC';

                // Build sort URL
                $sort_url = add_query_arg([
                    'orderby' => $column_key,
                    'order' => $next_order
                ]);

                // Preserve folder_id if set
                if (isset($_GET['folder_id'])) {
                    $sort_url = add_query_arg('folder_id', sanitize_text_field($_GET['folder_id']), $sort_url);
                }

                // Preserve search if set
                if (get_search_query()) {
                    $sort_url = add_query_arg('s', get_search_query(), $sort_url);
                }
            ?>
                <th class="wizard-table-<?php echo esc_attr($column_key); ?>">
                    <a href="<?php echo esc_url($sort_url); ?>"
                        class="wizard-sort-link <?php echo $is_current ? 'active' : ''; ?>"
                        data-sort="<?php echo esc_attr($column_key); ?>">
                        <?php echo esc_html($column['label']); ?>
                        <?php if ($is_current): ?>
                            <i class="fa-solid fa-chevron-<?php echo $current_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                        <?php endif; ?>
                    </a>
                </th>
            <?php endforeach; ?>
            <th class="wizard-table-template-actions">Actions</th>
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
        if (
            $this->folder_id === 'trash'
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
    private function render_templates($folder_id, $subfolders_count = 0)
    {
        $page = isset($this->args['page']) ? (int)$this->args['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : (isset($this->args['per_page']) ? (int)$this->args['per_page'] : 10);

        $templates_data = $this->get_templates($folder_id, array_merge($this->args, [
            'page' => $page,
            'per_page' => $per_page
        ]));

        $templates = $templates_data['templates'];
        $total_templates = $templates_data['total'];
        $max_pages = $templates_data['max_pages'];

        ob_start();

         // Hide pagination if there are no results or if folder is empty
        if ($total_templates > 0) {
            echo $this->render_pagination($page, $max_pages, $total_templates, $per_page);
        }

        // No results message
        if (empty($templates) && $page === 1) {
            if (get_search_query()) {
                echo '<tr class="no-results-message">';
                echo '<td colspan="5" class="wizard-table-message">';
                echo '<div class="wizard-message-wrapper">';
                echo '<i class="fa-solid fa-search"></i>';
                echo '<p>No templates found matching "' . esc_html(get_search_query()) . '"</p>';
                echo '<p class="wizard-message-subtitle">Try adjusting your search terms or <a href="' . esc_url(remove_query_arg('s')) . '">browse all templates</a></p>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            } elseif ($folder_id === 'trash') {
                echo '<tr class="no-results-message">';
                echo '<td colspan="5" class="wizard-table-message">';
                echo '<div class="wizard-message-wrapper">';
                echo '<i class="fa-solid fa-trash"></i>';
                echo '<p>The trash is empty</p>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            } elseif ($subfolders_count === 0) {
                echo '<tr class="no-results-message">';
                echo '<td colspan="5" class="wizard-table-message">';
                echo '<div class="wizard-message-wrapper">';
                echo '<i class="fa-regular fa-folder-open"></i>';
                echo '<p>No templates or folders here!</p>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            // Render templates
            foreach ($templates as $template) {
                echo $this->render_template_row($template['id'], $folder_id, $this->args['show_row_breadcrumb'] ?? false);
            }
        }

        // Hide pagination if there are no results or if folder is empty
        if ($total_templates > 0) {
            echo $this->render_pagination($page, $max_pages, $total_templates, $per_page);
        }

        return ob_get_clean();
    }



    private function render_pagination($current_page, $max_pages, $total_items, $per_page)
    {
        // Get the actual current page from the URL
        $current_page = max(1, get_query_var('paged'));

        ob_start();
    ?>
        <tr class="wizard-pagination-row">
            <td colspan="5">
                <div class="wizard-pagination">
                    <div class="pagination-info">
                        <?php
                        if ($total_items === 0) {
                            $start = 0;
                            $end = 0;
                        } else {
                            $start = (($current_page - 1) * $per_page) + 1;
                            $end = min($current_page * $per_page, $total_items);
                        }
                        ?>
                        <div class="pagination-info-text">
                            <?php printf('Showing %d-%d of %d templates', $start, $end, $total_items); ?>
                        </div>

                    </div>
                    <div class="per-page-control wizard-form-fieldgroup">
                        <select id="per-page-select" class="wizard-per-page-select ">
                            <?php
                            $options = [10, 25, 50, 100];
                            foreach ($options as $option) {
                                printf(
                                    '<option value="%d" %s>%d per page</option>',
                                    $option,
                                    selected($per_page, $option, false),
                                    $option
                                );
                            }
                            ?>
                        </select>
                    </div>
                    <?php if ($max_pages > 1): ?>
                        <div class="pagination-controls">
                            <?php
                            // Preserve all current query parameters
                            $query_args = $_GET;
                            unset($query_args['paged']); // Remove paged parameter as it's handled by paginate_links

                            // Build pagination links
                            $paginationBase = paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '<button class="wizard-button small" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>',
                                'next_text' => '<button class="wizard-button small" title="Next"><i class="fa-solid fa-chevron-right"></i></button>',
                                'current' => $current_page,
                                'total' => $max_pages,
                                'type' => 'list',
                                'add_args' => $query_args
                            ));
                            echo str_replace("<ul class='page-numbers'>", '<ul class="pagination">', $paginationBase);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
<?php
        return ob_get_clean();
    }

    private function render_folder_row($folder_id)
    {
        $subfolder = $this->user_folders->get_folder($folder_id);

        $teams = new WizardTeams();
        $active_team = $teams->get_active_team($this->user_id);
        $folderClass = 'wizard-folder-row';
        if (isset($subfolder['team_id']) && $subfolder['team_id'] == $active_team) {
            $folderClass .= ' team-folder';
        }

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
        echo '<tr data-type="folder" id="folder-' . $subfolder['id'] . '" data-id="' . $subfolder['id'] . '" class="' . $folderClass . '">';
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

    private function render_template_row($template_id)
    {
        $templateManager = new WizardTemplateManager();
        $template = $templateManager->get_template($template_id);

        if (!$template) {
            return '';
        }

        $lastUpdated = $template['updated_at']
            ? (new DateTime($template['updated_at']))->format('m/j/Y\, g:i A')
            : 'n/a';

        $thisTemplatesFolder = $template['folder_id'];
        $isChildOfCurrentFolder = $this->folder_id == $thisTemplatesFolder;

        // Show breadcrumb during search
        $breadcrumbHtml = '';
        if (get_search_query()) {
            $breadcrumbHtml = '<div class="template-row-breadcrumb">';
            if ($isChildOfCurrentFolder) {
                $breadcrumbHtml .= "<span class='breadcrumb-item'>Current folder</span>";
            } else {
                $breadcrumbHtml .= $this->generate_folder_breadcrumb($thisTemplatesFolder, true);
            }
            $breadcrumbHtml .= '</div>';
        }

        ob_start();
        $templatePermalink = get_the_permalink($template['id']);
        echo '<tr data-type="template" id="template-' . $template['id'] . '" data-id="' . $template['id'] . '">';
        echo '<td class="wizard-table-bulk-check template"><input type="checkbox" name="' . $template['id'] . '_checkbox"value="' . $template['id'] . '" data-type="template"></td>';
        echo '<td class="wizard-table-icon template"><i class="fa-solid fa-file-code"></i></td>';
        echo '<td class="wizard-table-template-name"><a class="wizard-table-template-name-link" href="' . $templatePermalink . '">' . $template['title'] . '</a>' . $breadcrumbHtml . '</td>';
        echo '<td class="wizard-table-last-modified">' . ($lastUpdated > 0 ? $lastUpdated : "n/a") . '</td>';
        echo '<td class="wizard-table-template-actions"><div class="table-template-actions-inner">';
        if ($this->folder_id === 'trash') {
            echo '<i title="Restore Template" class="fa-solid fa-trash-restore restore-template" data-type="template" data-template-id="' . $template['id'] . '"></i>
                  <i title="Delete Permanently" class="fa-solid fa-trash-alt delete-forever" data-type="template" data-template-id="' . $template['id'] . '"></i>';
        } else {
            echo '<i title="Duplicate Template" class="fa-solid fa-copy duplicate-template" data-template-id="' . $template['id'] . '"></i>
        <i title="Move Template" class="fa-solid fa-folder-tree move-template" data-template-id="' . $template['id'] . '"></i>
        <i title="Trash Template" class="fa-solid fa-trash delete-template" data-template-id="' . $template['id'] . '"></i></div></td>';
        }
        echo '</tr>';
        return ob_get_clean();
    }
}
