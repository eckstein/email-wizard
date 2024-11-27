<?php
class WizardAvatar
{
    private $user_id;
    private $avatar_meta_key = 'local_avatar';

    public function __construct($user_id = null)
    {
        $this->user_id = $user_id ? $user_id : get_current_user_id();
        
        // Add filters for avatar handling
        add_filter('pre_get_avatar_data', [$this, 'custom_avatar_data'], 10, 2);
        // Disable Gravatar completely
        add_filter('option_show_avatars', '__return_true');
        add_filter('pre_option_avatar_default', [$this, 'override_default_avatar']);
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