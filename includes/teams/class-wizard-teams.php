<?php
class WizardTeams
{
    private $wpdb;
    const CURRENT_TEAM_META_KEY = 'wizard_current_team';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Create a new team
     * 
     * @param array $team_data Array containing team information
     * @return int|WP_Error Team ID on success, WP_Error on failure
     */
    public function create_team($team_data)
    {
        if (empty($team_data['name']) || empty($team_data['created_by'])) {
            return new WP_Error('missing_required_fields', 'Name and creator are required.');
        }

        // Get default avatar
        $default_avatar_id = get_option('wizard_default_avatar');

        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'teams',
            array(
                'name' => sanitize_text_field($team_data['name']),
                'description' => isset($team_data['description']) ? sanitize_textarea_field($team_data['description']) : '',
                'created_by' => absint($team_data['created_by']),
                'avatar' => $default_avatar_id ? absint($default_avatar_id) : null,
                'status' => 'active'
            ),
            array('%s', '%s', '%d', '%d', '%s')
        );

        if ($result === false) {
            return new WP_Error('team_creation_failed', 'Failed to create team: ' . $this->wpdb->last_error);
        }

        $team_id = $this->wpdb->insert_id;

        // Add creator as team admin
        $this->add_team_member($team_id, $team_data['created_by'], 'admin');

        return $team_id;
    }

    /**
     * Add a member to a team
     * 
     * @param int $team_id Team ID
     * @param int $user_id User ID
     * @param string $role Member role (default: 'member')
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function add_team_member($team_id, $user_id, $role = 'member')
    {
        // Validate inputs
        if (!$this->team_exists($team_id)) {
            return new WP_Error('invalid_team', 'Team does not exist.');
        }

        if (!get_user_by('id', $user_id)) {
            return new WP_Error('invalid_user', 'User does not exist.');
        }

        // Check if user is already a member
        if ($this->is_team_member($team_id, $user_id)) {
            return new WP_Error('already_member', 'User is already a team member.');
        }

        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'team_members',
            array(
                'team_id' => $team_id,
                'user_id' => $user_id,
                'role' => $role,
                'status' => 'active'
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('member_add_failed', 'Failed to add team member: ' . $this->wpdb->last_error);
        }

        do_action('wizard_team_member_added', $team_id, $user_id, $role);
        return true;
    }

    /**
     * Get team details
     * 
     * @param int $team_id Team ID
     * @return object|WP_Error Team data on success, WP_Error on failure
     */
    public function get_team($team_id)
    {
        $team = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}teams WHERE id = %d AND status = 'active'",
            $team_id
        ));

        if (!$team) {
            return new WP_Error('team_not_found', 'Team not found or inactive.');
        }

        return $team;
    }

    /**
     * Get all teams for a user
     * 
     * @param int $user_id User ID
     * @return array Array of team objects
     */
    public function get_user_teams($user_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT t.*, tm.role 
            FROM {$this->wpdb->prefix}teams t
            JOIN {$this->wpdb->prefix}team_members tm ON t.id = tm.team_id
            WHERE tm.user_id = %d 
            AND tm.status = 'active'
            AND t.status = 'active'",
            $user_id
        ));
    }

    /**
     * Get all members of a team
     * 
     * @param int $team_id Team ID
     * @return array Array of user objects with role information
     */
    public function get_team_members($team_id)
    {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT u.ID, u.user_email, u.display_name, tm.role, tm.status, tm.created_at as joined_at
            FROM {$this->wpdb->prefix}users u
            JOIN {$this->wpdb->prefix}team_members tm ON u.ID = tm.user_id
            WHERE tm.team_id = %d 
            AND tm.status = 'active'
            ORDER BY tm.role = 'admin' DESC, u.display_name ASC",
            $team_id
        ));
    }

    /**
     * Update team details
     * 
     * @param int $team_id Team ID
     * @param array $team_data Array of team data to update
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_team($team_id, $team_data)
    {
        if (!$this->team_exists($team_id)) {
            return new WP_Error('invalid_team', 'Team does not exist.');
        }

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'teams',
            array(
                'name' => $team_data['name'],
                'description' => $team_data['description']
            ),
            array('id' => $team_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('team_update_failed', 'Failed to update team: ' . $this->wpdb->last_error);
        }

        return true;
    }

    /**
     * Remove team member
     * 
     * @param int $team_id Team ID
     * @param int $user_id User ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function remove_team_member($team_id, $user_id)
    {
        // Check if user is team owner (created_by)
        $team = $this->get_team($team_id);
        if (is_wp_error($team)) {
            return $team;
        }

        if ($team->created_by == $user_id) {
            return new WP_Error('remove_owner', 'Cannot remove team owner.');
        }

        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'team_members',
            [
                'team_id' => $team_id,
                'user_id' => $user_id
            ],
            ['%d', '%d']
        );

        if ($result === false) {
            return new WP_Error('delete_failed', 'Failed to remove team member: ' . $this->wpdb->last_error);
        }

        return true;
    }

    /**
     * Check if a user is a member of a team
     * 
     * @param int $team_id Team ID
     * @param int $user_id User ID
     * @return bool True if user is a member
     */
    public function is_team_member($team_id, $user_id)
    {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->wpdb->prefix}team_members 
            WHERE team_id = %d 
            AND user_id = %d 
            AND status = 'active'",
            $team_id,
            $user_id
        ));

        return $result > 0;
    }

    /**
     * Check if a user is a team admin
     * 
     * @param int $team_id Team ID
     * @param int $user_id User ID
     * @return bool True if user is an admin
     */
    public function is_team_admin($team_id, $user_id)
    {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->wpdb->prefix}team_members 
            WHERE team_id = %d 
            AND user_id = %d 
            AND role = 'admin' 
            AND status = 'active'",
            $team_id,
            $user_id
        ));

        return $result > 0;
    }

    /**
     * Delete a team (soft delete)
     * 
     * @param int $team_id Team ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function delete_team($team_id)
    {
        if (!$this->team_exists($team_id)) {
            return new WP_Error('invalid_team', 'Team does not exist.');
        }

        // Soft delete the team
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'teams',
            array('status' => 'deleted'),
            array('id' => $team_id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('team_delete_failed', 'Failed to delete team: ' . $this->wpdb->last_error);
        }

        // Soft delete all team memberships
        $this->wpdb->update(
            $this->wpdb->prefix . 'team_members',
            array('status' => 'inactive'),
            array('team_id' => $team_id),
            array('%s'),
            array('%d')
        );

        do_action('wizard_team_deleted', $team_id);
        return true;
    }

    /**
     * Check if a team exists
     * 
     * @param int $team_id Team ID
     * @return bool True if team exists
     */
    private function team_exists($team_id)
    {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}teams WHERE id = %d",
            $team_id
        ));

        return $result > 0;
    }

    /**
     * Switch user's current active team
     * 
     * @param int $user_id User ID
     * @param int $team_id Team ID to switch to
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function switch_active_team($user_id, $team_id)
    {
        // Verify team exists and user is a member
        if (!$this->is_team_member($team_id, $user_id)) {
            return new WP_Error('invalid_team_member', 'User is not a member of this team.');
        }

        $result = update_user_meta($user_id, self::CURRENT_TEAM_META_KEY, $team_id);

        if ($result === false) {
            return new WP_Error('team_switch_failed', 'Failed to switch active team.');
        }

        do_action('wizard_team_switched', $user_id, $team_id);
        return true;
    }

    /**
     * Get user's current active team
     * 
     * @param int $user_id User ID
     * @return int|false Team ID if set, false if no team is set
     */
    public function get_active_team($user_id)
    {
        // Return early if user is not logged in
        if (!is_user_logged_in()) {
            return false;
        }

        $team_id = get_user_meta($user_id, self::CURRENT_TEAM_META_KEY, true);

        // If no team is set or the team is invalid/inactive, get the first available team
        if (!$team_id || !$this->is_team_member($team_id, $user_id)) {
            $user_teams = $this->get_user_teams($user_id);
            if (!empty($user_teams)) {
                $team_id = $user_teams[0]->id;
                $this->switch_active_team($user_id, $team_id);
            } else {
                return false;
            }
        }

        return $team_id;
    }

    public function get_active_team_name($userId) {
        // Return early if user is not logged in
        if (!is_user_logged_in()) {
            return '';
        }

        $team_id = $this->get_active_team($userId);
        if (!$team_id) {
            return '';
        }
        
        $team = $this->get_team($team_id);
        return is_wp_error($team) ? '' : $team->name;
    }

    /**
     * Clear user's active team
     * 
     * @param int $user_id User ID
     * @return bool True on success
     */
    public function clear_active_team($user_id)
    {
        delete_user_meta($user_id, self::CURRENT_TEAM_META_KEY);
        return true;
    }

    public function get_member_role($team_id, $user_id) {
        $role = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT role 
            FROM {$this->wpdb->prefix}team_members 
            WHERE team_id = %d 
            AND user_id = %d 
            AND status = 'active'",
            $team_id,
            $user_id
        ));

        return $role;
    }

    /**
     * Update team member role
     * 
     * @param int $team_id Team ID
     * @param int $user_id User ID
     * @param string $role New role (admin or member)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_member_role($team_id, $user_id, $role)
    {
        if (!in_array($role, ['admin', 'member'])) {
            return new WP_Error('invalid_role', 'Invalid role specified.');
        }

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'team_members',
            ['role' => $role],
            [
                'team_id' => $team_id,
                'user_id' => $user_id
            ],
            ['%s'],
            ['%d', '%d']
        );

        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update member role: ' . $this->wpdb->last_error);
        }

        return true;
    }

    public function is_regular_member($team_id, $user_id) {
        $role = $this->get_member_role($team_id, $user_id);
        return $role === 'member';
    }

    /**
     * Update team avatar
     * 
     * @param int $team_id Team ID
     * @param array $file $_FILES array element
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_team_avatar($team_id, $file) {
        if (!$this->team_exists($team_id)) {
            return new WP_Error('invalid_team', 'Team does not exist.');
        }

        // Use WizardAvatar for validation and processing
        $avatar_handler = new WizardAvatar();
        $attachment_id = $avatar_handler->handle_upload($file);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Delete old avatar if exists
        $this->delete_team_avatar($team_id);

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'teams',
            array('avatar' => $attachment_id),
            array('id' => $team_id),
            array('%d'),
            array('%d')
        );

        if ($result === false) {
            // Clean up the uploaded file if database update fails
            wp_delete_attachment($attachment_id, true);
            return new WP_Error('avatar_update_failed', 'Failed to update team avatar: ' . $this->wpdb->last_error);
        }

        return true;
    }

    /**
     * Delete team avatar
     * 
     * @param int $team_id Team ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function delete_team_avatar($team_id) {
        $team = $this->get_team($team_id);
        if (is_wp_error($team)) {
            return $team;
        }

        if ($team->avatar) {
            wp_delete_attachment($team->avatar, true);
        }

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'teams',
            array('avatar' => null),
            array('id' => $team_id),
            array('%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('avatar_delete_failed', 'Failed to delete team avatar: ' . $this->wpdb->last_error);
        }

        return true;
    }

    /**
     * Get team avatar URL
     * 
     * @param int $team_id Team ID
     * @param int $size Avatar size in pixels
     * @return string|false Avatar URL or false if no avatar
     */
    public function get_team_avatar_url($team_id, $size = 96) {
        $team = $this->get_team($team_id);
        if (is_wp_error($team) || !$team->avatar) {
            return false;
        }

        return wp_get_attachment_image_url($team->avatar, array($size, $size));
    }

    /**
     * Get team avatar HTML
     * 
     * @param int $team_id Team ID
     * @param int $size Avatar size in pixels
     * @return string Avatar HTML
     */
    public function get_team_avatar($team_id, $size = 96) {
        $team = $this->get_team($team_id);
        if (is_wp_error($team)) {
            return '';
        }

        // First try team's specific avatar
        if ($team->avatar) {
            $image_url = wp_get_attachment_image_url($team->avatar, array($size, $size));
            if ($image_url) {
                return wp_get_attachment_image($team->avatar, array($size, $size), false, array(
                    'class' => 'team-avatar',
                    'alt' => esc_attr($team->name) . ' team avatar'
                ));
            }
        }

        // If no team avatar, try default avatar
        $default_avatar_id = get_option('wizard_default_avatar');
        if ($default_avatar_id) {
            $image_url = wp_get_attachment_image_url($default_avatar_id, array($size, $size));
            if ($image_url) {
                return wp_get_attachment_image($default_avatar_id, array($size, $size), false, array(
                    'class' => 'team-avatar',
                    'alt' => esc_attr($team->name) . ' team avatar'
                ));
            }
        }

        // Fallback to default avatar with first letter
        return sprintf(
            '<div class="team-avatar default-avatar" style="width: %1$dpx; height: %1$dpx;">%2$s</div>',
            $size,
            esc_html(substr($team->name, 0, 1))
        );
    }

    /**
     * Check if team has a custom avatar
     * 
     * @param int $team_id Team ID
     * @return bool True if team has custom avatar
     */
    public function has_team_avatar($team_id) {
        $team = $this->get_team($team_id);
        if (is_wp_error($team)) {
            return false;
        }
        return !empty($team->avatar);
    }

    /**
     * Create or reactivate a team invitation
     * 
     * @param int $team_id Team ID
     * @param string $email Invitee email
     * @param int $invited_by User ID who sent the invite
     * @param string $role Member role (default: 'member')
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function create_team_invite($team_id, $email, $invited_by, $role = 'member') {
        if (!$this->team_exists($team_id)) {
            return new WP_Error('invalid_team', 'Team does not exist.');
        }

        // Check if user is already an active member
        $existing_user = get_user_by('email', $email);
        if ($existing_user && $this->is_team_member($team_id, $existing_user->ID)) {
            return new WP_Error('already_member', 'User is already a team member.');
        }

        // Check for existing invite
        $existing_invite = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}team_invites 
            WHERE team_id = %d AND email = %s",
            $team_id,
            $email
        ));

        // Generate token and expiration
        $token = wp_generate_password(32, false);
        $expires_at = date('Y-m-d H:i:s', strtotime('+48 hours'));

        if ($existing_invite) {
            // Update existing invite
            $result = $this->wpdb->update(
                $this->wpdb->prefix . 'team_invites',
                array(
                    'token' => $token,
                    'role' => $role,
                    'status' => 'pending',
                    'invited_by' => $invited_by,
                    'expires_at' => $expires_at
                ),
                array('id' => $existing_invite->id),
                array('%s', '%s', '%s', '%d', '%s'),
                array('%d')
            );

            if ($result === false) {
                return new WP_Error('invite_update_failed', 'Failed to update invitation.');
            }

            $invite_id = $existing_invite->id;
        } else {
            // Create new invite
            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'team_invites',
                array(
                    'team_id' => $team_id,
                    'email' => $email,
                    'token' => $token,
                    'role' => $role,
                    'invited_by' => $invited_by,
                    'expires_at' => $expires_at
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s')
            );

            if ($result === false) {
                return new WP_Error('invite_failed', 'Failed to create invitation.');
            }

            $invite_id = $this->wpdb->insert_id;
        }

        // TODO: Send invitation email
        do_action('wizard_team_invite_created', $invite_id, $team_id, $email, $token);

        return true;
    }

    /**
     * Get pending invites for a team
     * 
     * @param int $team_id Team ID
     * @return array Array of invite objects
     */
    public function get_team_invites($team_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}team_invites 
            WHERE team_id = %d 
            AND status = 'pending'
            AND expires_at > NOW()",
            $team_id
        ));
    }

    /**
     * Revoke a team invitation
     * 
     * @param int $invite_id Invite ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function revoke_team_invite($invite_id) {
        error_log('Attempting to revoke invite: ' . $invite_id);
        
        // First verify the invite exists
        $invite = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}team_invites WHERE id = %d",
            $invite_id
        ));

        if (!$invite) {
            error_log('Invite not found: ' . $invite_id);
            return new WP_Error('invalid_invite', 'Invite not found.');
        }

        error_log('Found invite, current status: ' . $invite->status);

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'team_invites',
            array('status' => 'revoked'),
            array('id' => $invite_id),
            array('%s'),
            array('%d')
        );

        error_log('Update result: ' . ($result === false ? 'failed' : 'success'));
        if ($result === false) {
            error_log('MySQL error: ' . $this->wpdb->last_error);
            return new WP_Error('revoke_failed', 'Failed to revoke invitation: ' . $this->wpdb->last_error);
        }

        return true;
    }
}
