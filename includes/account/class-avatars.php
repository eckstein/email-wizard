<?php
class WizardAvatar
{
    private $user_id;
    private $avatar_meta_key = 'local_avatar';
    private $errors = [];

    public function __construct($user_id = null)
    {
        $this->user_id = $user_id ? $user_id : get_current_user_id();
        
        // Add filters for avatar handling
        add_filter('pre_get_avatar_data', [$this, 'custom_avatar_data'], 10, 2);
        // Disable Gravatar completely
        add_filter('option_show_avatars', '__return_true');
        add_filter('pre_option_avatar_default', [$this, 'override_default_avatar']);
    }

    /**
     * Handle avatar upload and validation
     * 
     * @param array $file $_FILES array element
     * @param int $max_size Maximum file size in bytes (default 5MB)
     * @return int|WP_Error Attachment ID on success, WP_Error on failure
     */
    public function handle_upload($file, $max_size = 5242880)
    {
        // Check for PHP upload errors first
        if (!empty($file['error'])) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    return new WP_Error('file_too_large', 'The uploaded file exceeds the upload_max_filesize directive in php.ini');
                case UPLOAD_ERR_FORM_SIZE:
                    return new WP_Error('file_too_large', 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form');
                case UPLOAD_ERR_PARTIAL:
                    return new WP_Error('upload_error', 'The file was only partially uploaded');
                case UPLOAD_ERR_NO_FILE:
                    return new WP_Error('no_file', 'No file was uploaded');
                case UPLOAD_ERR_NO_TMP_DIR:
                    return new WP_Error('server_error', 'Missing a temporary folder');
                case UPLOAD_ERR_CANT_WRITE:
                    return new WP_Error('server_error', 'Failed to write file to disk');
                case UPLOAD_ERR_EXTENSION:
                    return new WP_Error('server_error', 'A PHP extension stopped the file upload');
                default:
                    return new WP_Error('upload_error', 'Unknown upload error');
            }
        }

        // Basic validation
        if (!$file || !isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return new WP_Error('no_file', 'No file was uploaded.');
        }

        // File size validation
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', sprintf(
                'The uploaded file exceeds the maximum allowed size of %s MB.',
                number_format($max_size / (1024 * 1024), 1)
            ));
        }

        // MIME type validation
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = wp_check_filetype($file['name']);
        
        if (!$file_type['type'] || !in_array($file_type['type'], $allowed_types)) {
            return new WP_Error('invalid_type', 'The uploaded file type is not allowed. Please use JPG, PNG, or GIF.');
        }

        // Image dimension validation
        $image_size = getimagesize($file['tmp_name']);
        if (!$image_size) {
            return new WP_Error('invalid_image', 'The uploaded file is not a valid image.');
        }

        // Minimum dimensions
        if ($image_size[0] < 96 || $image_size[1] < 96) {
            return new WP_Error('image_too_small', 'The image must be at least 96x96 pixels.');
        }

        // Maximum dimensions
        if ($image_size[0] > 2048 || $image_size[1] > 2048) {
            return new WP_Error('image_too_large', 'The image dimensions must not exceed 2048x2048 pixels.');
        }

        // Prepare upload
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Upload and attachment creation
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error']);
        }

        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attach_id)) {
            return $attach_id;
        }

        // Generate metadata and thumbnails
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    /**
     * Set avatar for a user
     * 
     * @param int $attachment_id The attachment ID to use as avatar
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function set_avatar($attachment_id)
    {
        if (!current_user_can('edit_user', $this->user_id)) {
            return new WP_Error('permission_denied', 'You do not have permission to edit this user\'s avatar.');
        }

        // Verify attachment exists and is an image
        if (!wp_attachment_is_image($attachment_id)) {
            return new WP_Error('invalid_attachment', 'The provided attachment is not a valid image.');
        }

        // Delete existing avatar if present
        $this->delete_avatar();

        // Set new avatar
        update_user_meta($this->user_id, $this->avatar_meta_key, $attachment_id);

        return true;
    }

    /**
     * Delete the user's avatar
     * 
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function delete_avatar()
    {
        if (!current_user_can('edit_user', $this->user_id)) {
            return new WP_Error('permission_denied', 'You do not have permission to delete this user\'s avatar.');
        }

        $avatar_id = get_user_meta($this->user_id, $this->avatar_meta_key, true);
        
        if ($avatar_id) {
            wp_delete_attachment($avatar_id, true);
            delete_user_meta($this->user_id, $this->avatar_meta_key);
        }

        return true;
    }

    public function custom_avatar_data($args, $id_or_email)
    {
        $user_id = $this->get_user_id_from_identifier($id_or_email);
        
        if ($user_id) {
            $local_avatar_id = get_user_meta($user_id, $this->avatar_meta_key, true);
            if ($local_avatar_id) {
                $image_url = wp_get_attachment_image_url($local_avatar_id, 'full');
                if ($image_url) {
                    $args['url'] = $image_url;
                    $args['found_avatar'] = true;
                }
            } else {
                // Use default avatar if no custom avatar is set
                $default_avatar_id = get_option('wizard_default_avatar');
                if ($default_avatar_id) {
                    $image_url = wp_get_attachment_image_url($default_avatar_id, 'full');
                    if ($image_url) {
                        $args['url'] = $image_url;
                        $args['found_avatar'] = true;
                    }
                }
            }
        }
        
        // Force disable Gravatar
        $args['url'] = $args['url'] ?? $this->get_default_avatar_url();
        $args['found_avatar'] = true;
        
        return $args;
    }

    public function override_default_avatar()
    {
        return 'custom';
    }

    private function get_default_avatar_url()
    {
        $default_avatar_id = get_option('wizard_default_avatar');
        if ($default_avatar_id) {
            return wp_get_attachment_image_url($default_avatar_id, 'full');
        }
        // Fallback to WordPress default
        return includes_url('images/blank.gif');
    }

    private function get_user_id_from_identifier($id_or_email)
    {
        if (is_numeric($id_or_email)) {
            return (int) $id_or_email;
        } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
            return (int) $id_or_email->user_id;
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            return $user ? $user->ID : 0;
        }
        return 0;
    }

    public function get_avatar($size = 96)
    {
        return get_avatar($this->user_id, $size);
    }

    public function get_avatar_url($size = 96)
    {
        return get_avatar_url($this->user_id, ['size' => $size]);
    }

    public function has_custom_avatar()
    {
        return (bool) get_user_meta($this->user_id, $this->avatar_meta_key, true);
    }
}

?>