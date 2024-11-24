<?php
class WizardAccount
{
    private $tabs;
    private $user;
    private $is_authorized;
    public $messages = array();

    public function __construct()
    {
        $this->is_authorized = is_user_logged_in();
        if ($this->is_authorized) {
            $this->user = wp_get_current_user();
            $this->tabs = [];
            $this->register_default_tabs();
            $this->handle_form_submission();
        }
    }

    private function register_default_tabs()
    {
        $this->add_tab([
            'id' => 'account',
            'title' => 'Your Info',
            'icon' => 'fa-solid fa-user',
            'template' => 'tab-account.php'
        ]);

        $this->add_tab([
            'id' => 'teams',
            'title' => 'Your Teams',
            'icon' => 'fa-solid fa-users',
            'template' => 'tab-teams.php'
        ]);

        $this->add_tab([
            'id' => 'plan',
            'title' => 'Manage Plan',
            'icon' => 'fa-solid fa-sliders',
            'template' => 'tab-plan.php'
        ]);

        $this->add_tab([
            'id' => 'billing',
            'title' => 'Billing Settings',
            'icon' => 'fa-solid fa-credit-card',
            'template' => 'tab-billing.php'
        ]);
    }

    public function add_tab($tab)
    {
        $this->tabs[] = $tab;
    }

    private function generate_tab_list()
    {
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

    private function load_tab_template($template_name)
    {
        if (!$this->is_authorized) {
            return '';
        }

        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/templates/account/' . $template_name;
        if (!file_exists($template_path)) {
            return "Template not found: " . esc_html($template_path);
        }

        ob_start();
        load_template($template_path, false);
        return ob_get_clean();
    }

    public function render()
    {
        if (!$this->is_authorized) {
            return '<p>' . esc_html__('You must be logged in to view this page.', 'wizard') . '</p>';
        }

        ob_start();
        ?>
        <?php if (!empty($this->messages)) { ?>
            <?php foreach ($this->messages as $message) { ?>
                <div class="wizard-message wizard-message-<?php echo esc_attr($message['type']); ?>">
                    <?php echo esc_html($message['text']); ?>
                </div>
            <?php } ?>
        <?php } ?>

        <div class="wizard-tabs" id="account-menu-tabs">
            <?php echo $this->generate_tab_list(); ?>

            <div class="wizard-tab-panels">
                <form method="post" action="" enctype="multipart/form-data">
                    <?php foreach ($this->tabs as $index => $tab) { ?>
                        <div class="wizard-tab-content <?php echo $index === 0 ? 'active' : ''; ?>"
                            data-content="tab-<?php echo esc_attr($tab['id']); ?>">
                            <?php echo $this->load_tab_template($tab['template']); ?>
                        </div>
                    <?php } ?>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function handle_form_submission()
    {
        if (!isset($_POST['wizard_account_action'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['wizard_account_nonce'], 'wizard_account_info')) {
            $this->messages[] = [
                'type' => 'error',
                'text' => 'Security verification failed.'
            ];
            return;
        }

        switch ($_POST['wizard_account_action']) {
            case 'update_account':
                $this->process_account_update();
                break;
        }
    }

    public function process_account_update()
    {
        $user_id = get_current_user_id();
        $user_data = array();
        $updated = false;

        // Handle basic user data updates
        if (isset($_POST['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
            update_user_meta($user_id, 'first_name', $user_data['first_name']);
            $updated = true;
        }

        if (isset($_POST['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
            update_user_meta($user_id, 'last_name', $user_data['last_name']);
            $updated = true;
        }

        if (isset($_POST['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($_POST['display_name']);
            $updated = true;
        }

        if (isset($_POST['user_email']) && is_email($_POST['user_email'])) {
            $user_data['user_email'] = sanitize_email($_POST['user_email']);
            $updated = true;
        }

        // Handle password update if provided
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            $user = get_user_by('id', $user_id);
            if (wp_check_password($_POST['current_password'], $user->data->user_pass, $user_id)) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $user_data['user_pass'] = $_POST['new_password'];
                    $updated = true;
                } else {
                    $this->messages[] = [
                        'type' => 'error',
                        'text' => 'New passwords do not match.'
                    ];
                }
            } else {
                $this->messages[] = [
                    'type' => 'error',
                    'text' => 'Current password is incorrect.'
                ];
            }
        }

        // Update user data if we have changes
        if (!empty($user_data)) {
            $user_data['ID'] = $user_id;
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                $this->messages[] = [
                    'type' => 'error',
                    'text' => $result->get_error_message()
                ];
            } else {
                $updated = true;
            }
        }

        // Handle avatar deletion if requested
        if (isset($_POST['delete_avatar'])) {
            $avatar_id = get_user_meta($user_id, 'local_avatar', true);
            if ($avatar_id) {
                wp_delete_attachment($avatar_id, true);
                delete_user_meta($user_id, 'local_avatar');
                $updated = true;
            }
        }

        // Handle avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            // Check file type
            $file_type = wp_check_filetype($_FILES['avatar']['name']);
            if (!in_array($file_type['ext'], array('jpg', 'jpeg', 'png', 'gif'))) {
                $this->messages[] = [
                    'type' => 'error',
                    'text' => 'Invalid file type. Please upload an image file (JPG, PNG, or GIF).'
                ];
                return;
            }

            // Check file size (5MB limit)
            if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                $this->messages[] = [
                    'type' => 'error',
                    'text' => 'File is too large. Maximum size is 5MB.'
                ];
                return;
            }

            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
            }

            // Delete existing avatar first
            $existing_avatar_id = get_user_meta($user_id, 'local_avatar', true);
            if ($existing_avatar_id) {
                wp_delete_attachment($existing_avatar_id, true);
            }

            // Upload the file
            $avatar_id = media_handle_upload('avatar', 0);

            if (is_wp_error($avatar_id)) {
                $this->messages[] = [
                    'type' => 'error',
                    'text' => 'Failed to upload avatar: ' . $avatar_id->get_error_message()
                ];
                return;
            }

            // Update user meta with new avatar
            update_user_meta($user_id, 'local_avatar', $avatar_id);
            
            $this->messages[] = [
                'type' => 'success',
                'text' => 'Avatar updated successfully.'
            ];
            return;
        }

        // Add success message if anything was updated
        if ($updated && empty($this->messages)) {
            $this->messages[] = [
                'type' => 'success',
                'text' => 'Account information updated successfully.'
            ];
        }
    }
}