<?php

class WizardTabs {
    protected $tabs = [];
    protected $is_authorized = false;
    protected $messages = [];
    protected $handlers = [];
    
    public function __construct() {
        $this->is_authorized = is_user_logged_in();
    }
    
    public function add_tab($tab) {
        $this->tabs[] = $tab;
    }
    
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
    
    protected function load_tab_template($template_name) {
        if (!$this->is_authorized) {
            return '';
        }

        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/templates/account/' . $template_name;
        if (!file_exists($template_path)) {
            return "Template not found: " . esc_html($template_path);
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
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
                        <?php echo $this->load_tab_template($tab['template']); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    protected function get_container_id() {
        return 'wizard-tabs-container';
    }
}
