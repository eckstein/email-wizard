<?php
/**
 * Abstract base class for creating tabbed interfaces in WordPress.
 * 
 * This class provides a foundation for creating tabbed interfaces with template support
 * and WordPress theme override capabilities. It handles tab registration, template loading,
 * and rendering of tab content.
 * 
 * @since 1.0.0
 */
abstract class WizardTabs {
    /** @var array List of registered tabs */
    protected $tabs = [];

    /** @var bool Whether the current user is authorized to view tabs */
    protected $is_authorized = false;

    /** @var array List of messages to display */
    protected $messages = [];

    /** @var array List of registered form handlers */
    protected $handlers = [];
    
    /**
     * Initialize the tabs interface.
     * 
     * Sets up authorization check on construction.
     */
    public function __construct() {
        $this->is_authorized = is_user_logged_in();
    }
    
    /**
     * Register a new tab.
     * 
     * @param array $tab {
     *     Tab configuration array.
     *     
     *     @type string $id             Unique identifier for the tab
     *     @type string $title          Display title for the tab
     *     @type string $icon           CSS class for the tab icon
     *     @type array  $template_paths Optional. Custom template paths for this tab
     * }
     */
    public function add_tab($tab) {
        if (!isset($tab['template_paths'])) {
            $tab['template_paths'] = $this->get_default_template_paths($tab['id']);
        }
        $this->tabs[] = $tab;
    }

    /**
     * Get the plugin's templates directory path.
     * 
     * @return string Path to the templates directory
     */
    protected function get_templates_dir() {
        return plugin_dir_path(dirname(dirname(__FILE__))) . 'public/templates/';
    }

    /**
     * Get default template paths for a tab.
     * 
     * @param string $tab_id The tab identifier
     * @return array Array of possible template paths
     */
    protected function get_default_template_paths($tab_id) {
        return [
            get_stylesheet_directory() . '/wizard/' . $this->get_template_base() . '/tab-' . $tab_id . '.php',
            $this->get_templates_dir() . $this->get_template_base() . '/tab-' . $tab_id . '.php',
        ];
    }

    /**
     * Load and render a tab template.
     * 
     * @param string|array $template_paths Single template path or array of possible paths
     * @return string The rendered template content
     */
    protected function load_tab_template($template_paths) {
        if (!$this->is_authorized) {
            return '';
        }

        $theme_templates = array_map(function($path) {
            return 'wizard/' . $this->get_template_base() . '/' . basename($path);
        }, (array)$template_paths);

        $template = locate_template($theme_templates);
        
        if (!$template) {
            foreach ((array)$template_paths as $path) {
                if (file_exists($path)) {
                    $template = $path;
                    break;
                }
            }
        }

        if (!$template) {
            return sprintf(
                '<div class="wizard-tab-error">Template not found in paths: %s</div>', 
                esc_html(implode(', ', (array)$template_paths))
            );
        }

        ob_start();
        include $template;
        return ob_get_clean();
    }
    
    /**
     * Generate the HTML for the tab navigation list.
     * 
     * @return string HTML markup for the tab list
     */
    protected function generate_tab_list() {
        $output = '<ul class="wizard-tabs-list">';
        foreach ($this->tabs as $index => $tab) {
            $active = $index === 0 ? 'class="active"' : '';
            $output .= sprintf(
                '<li data-tab="tab-%s" %s>
                    <i class="%s"></i>
                    <span class="tab-item-label">%s</span>
                    <span class="tab-item-indicator"><i class="fa-solid fa-chevron-right"></i></span>
                </li>',
                esc_attr($tab['id']),
                $active,
                esc_attr($tab['icon']),
                esc_html($tab['title'])
            );
        }
        $output .= '</ul>';
        return $output;
    }
    
    /**
     * Render the complete tabs interface.
     * 
     * @return string Complete HTML markup for the tabs interface
     */
    public function render() {
        if (!$this->is_authorized) {
            return '<p>' . esc_html__('You must be logged in to view this page.', 'wizard') . '</p>';
        }

        ob_start();
        ?>
        <div class="wizard-tabs" id="<?php echo $this->get_container_id(); ?>">
            <?php echo $this->generate_tab_list(); ?>
            <div class="wizard-tab-panels">
                <?php foreach ($this->tabs as $index => $tab) { ?>
                    <div class="wizard-tab-content <?php echo $index === 0 ? 'active' : ''; ?>"
                        data-content="tab-<?php echo esc_attr($tab['id']); ?>">
                        <?php echo $this->load_tab_template($tab['template_paths']); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get the HTML container ID for this tabs interface.
     * 
     * @return string Container ID
     */
    abstract protected function get_container_id();

    /**
     * Get the base template directory name for this implementation.
     * 
     * @return string Template directory base name
     */
    abstract protected function get_template_base();
}
