<?php
class WizardAvatar
{
    private $user_id;
    private $avatar_meta_key = 'local_avatar';
    private $errors = [];

    public function __construct($user_id = null)
    {
        // Suppress deprecated function warnings
        set_error_handler(function($errno, $errstr) {
            return strpos($errstr, 'utf8_encode()') !== false;
        }, E_DEPRECATED);
        
        $this->user_id = $user_id ? $user_id : get_current_user_id();
        
        // Add filters for avatar handling
        add_filter('pre_get_avatar_data', [$this, 'custom_avatar_data'], 10, 2);
        // Disable Gravatar completely
        add_filter('option_show_avatars', '__return_true');
        add_filter('pre_option_avatar_default', [$this, 'override_default_avatar']);

        // Restore error handler
        restore_error_handler();
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

    /**
     * Override the default avatar with our custom fallback
     * 
     * @param string $default The default avatar URL
     * @return string Modified default avatar URL
     */
    public function override_default_avatar($default)
    {
        $fallback_id = get_option('wizard_default_avatar');
        if ($fallback_id) {
            $fallback_url = wp_get_attachment_url($fallback_id);
            if ($fallback_url) {
                return $fallback_url;
            }
        }
        // Return WordPress default if no custom fallback is set
        return $default;
    }

    /**
     * Filter the avatar data before displaying
     * 
     * @param array $args Avatar arguments
     * @param mixed $id_or_email User ID or email
     * @return array Modified avatar arguments
     */
    public function custom_avatar_data($args, $id_or_email)
    {
        // Get user ID from email if necessary
        if (is_string($id_or_email) && is_email($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            $user_id = $user ? $user->ID : null;
        } elseif (is_numeric($id_or_email)) {
            $user_id = $id_or_email;
        } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
            $user_id = $id_or_email->user_id;
        } else {
            $user_id = null;
        }

        // If no valid user found, return default avatar
        if (!$user_id) {
            $args['url'] = $this->override_default_avatar($args['default']);
            return $args;
        }

        // Get custom avatar
        $custom_avatar_id = get_user_meta($user_id, $this->avatar_meta_key, true);
        if ($custom_avatar_id) {
            $avatar_url = wp_get_attachment_url($custom_avatar_id);
            if ($avatar_url) {
                $args['url'] = $avatar_url;
                return $args;
            }
        }

        // Use custom default avatar if no custom avatar is set
        $args['url'] = $this->override_default_avatar($args['default']);
        return $args;
    }

    /**
     * Check if user has a custom avatar
     * 
     * @return boolean True if user has custom avatar
     */
    public function has_custom_avatar()
    {
        $avatar_id = get_user_meta($this->user_id, $this->avatar_meta_key, true);
        return !empty($avatar_id);
    }

    /**
     * Get avatar URL for the user
     * 
     * @param int $size Avatar size in pixels
     * @return string Avatar URL
     */
    public function get_avatar_url($size = 96)
    {
        $avatar_data = $this->custom_avatar_data(
            ['size' => $size, 'default' => get_option('avatar_default', 'mystery')],
            $this->user_id
        );
        return $avatar_data['url'];
    }

    /**
     * Get avatar HTML for the user
     * 
     * @param int $size Avatar size in pixels
     * @return string Avatar HTML
     */
    public function get_avatar($size = 96)
    {
        return get_avatar($this->user_id, $size);
    }
}

?>