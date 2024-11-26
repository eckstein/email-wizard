<?php

/**
 * Base form validating functionality for wizard forms.
 * 
 * Provides consistent form handling, validation, and sanitization across the plugin.
 * 
 * @since 1.0.0
 */
class WizardFormValidator
{
    /** @var array Validation/sanitization errors */
    protected $errors = [];

    /** @var array Processed and sanitized form data */
    protected $data = [];

    /** @var string The form action identifier */
    protected $action;

    /**
     * Initialize the form validator
     * 
     * @param string $action The form action identifier
     */
    public function __construct($action)
    {
        $this->action = $action;
    }

    /**
     * Verify nonce and initialize form validating
     * 
     * @return bool Whether the nonce is valid
     */
    public function verify_nonce()
    {
        $nonce_key = 'wizard_' . $this->action . '_nonce';
        $nonce_action = 'wizard_' . $this->action;

        return isset($_POST[$nonce_key]) && wp_verify_nonce($_POST[$nonce_key], $nonce_action);
    }

    /**
     * Sanitize and validate form fields
     * 
     * @param array $fields Array of field definitions
     * @return bool Whether all fields are valid
     */
    public function process_fields($fields)
    {
        foreach ($fields as $field => $rules) {
            if (isset($_POST[$field])) {
                $this->data[$field] = $this->sanitize_field($_POST[$field], $rules);
            }
        }

        return empty($this->errors);
    }

    /**
     * Sanitize a field value based on type and rules
     * 
     * @param mixed $value The field value
     * @param array $rules Validation/sanitization rules
     * @return mixed Sanitized value
     */
    protected function sanitize_field($value, $rules)
    {
        $type = $rules['type'] ?? 'text';

        switch ($type) {
            case 'email':
                $value = sanitize_email($value);
                if (!is_email($value)) {
                    $this->errors[] = 'Invalid email address';
                }
                break;

            case 'text':
                $value = sanitize_text_field($value);
                break;

            case 'file':
                // File validation handled separately
                break;

            case 'password':
                // Passwords shouldn't be sanitized but can be validated
                if (!empty($rules['confirm_field'])) {
                    if ($value !== $_POST[$rules['confirm_field']]) {
                        $this->errors[] = 'Passwords do not match';
                    }
                }
                break;
        }

        return $value;
    }

    /**
     * Process file upload
     * 
     * @param string $field File field name
     * @param array $allowed_types Allowed file extensions
     * @param int $max_size Maximum file size in bytes
     * @return int|WP_Error Attachment ID if successful
     */
    public function process_file($field, $allowed_types, $max_size)
    {
        if (empty($_FILES[$field]['name'])) {
            return false;
        }

        $file_type = wp_check_filetype($_FILES[$field]['name']);
        if (!in_array($file_type['ext'], $allowed_types)) {
            $this->errors[] = 'Invalid file type';
            return false;
        }

        if ($_FILES[$field]['size'] > $max_size) {
            $this->errors[] = 'File is too large';
            return false;
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        return media_handle_upload($field, 0);
    }

    /**
     * Get processed and sanitized data
     * 
     * @return array Processed form data
     */
    public function get_data()
    {
        return $this->data;
    }

    /**
     * Get validation errors
     * 
     * @return array Error messages
     */
    public function get_errors()
    {
        return $this->errors;
    }
}
