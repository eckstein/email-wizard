<?php
class WizardFolders
{
    private $wpdb;
    private $table_name;
    private $user_id;
    private $team_id;

    public function __construct($user_id, $team_id = null)
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return;
        }

        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'user_folders';
        $this->user_id = $user_id;
        
        $teams = new WizardTeams();
        $active_team = $teams->get_active_team($user_id);
        
        // If no active team, initialize with null
        if (!$active_team) {
            $this->team_id = null;
            return;
        }

        $team_id = $team_id ?? $active_team;
        
        // Validate team access
        $team_access = $this->validate_team_access($team_id);
        if (is_wp_error($team_access)) {
            $this->team_id = $active_team; // Fallback to active team
        } else {
            $this->team_id = $team_id;
        }
    }

    public function add_folder($folder_name, $parent_id = null)
    {
        $data = array(
            'name' => $folder_name,
            'parent_id' => $parent_id,
            'created_by' => $this->user_id,
            'team_id' => $this->team_id,
        );
        
        $result = $this->wpdb->insert($this->table_name, $data, array('%s', '%d', '%d', '%d', '%s'));

        if ($result === false) {
            return new WP_Error('insert_failed', 'Failed to add folder: ' . $this->wpdb->last_error);
        }

        return $this->wpdb->insert_id;
    }

    public function get_folders($exclude = [])
    {
        // Return early if user is not logged in
        if (!is_user_logged_in()) {
            return [];
        }

        $query = "SELECT * FROM {$this->table_name} WHERE (created_by = %d AND team_id = %d) ";
        $params = [$this->user_id, $this->team_id];

        if (!empty($exclude)) {
            $placeholders = implode(',', array_fill(0, count($exclude), '%d'));
            $query .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $exclude);
        }

        return $this->wpdb->get_results($this->wpdb->prepare($query, $params), ARRAY_A);
    }
    
    /**
     * Validates if the current user has access to the specified folder
     *
     * @param int $folder_id The ID of the folder to validate
     * @return array|WP_Error Folder data if accessible, WP_Error if not
     */
    private function validate_folder_access($folder_id) {
        if (!is_user_logged_in() || !$this->team_id) {
            return new WP_Error('not_logged_in', 'You must be logged in to access folders');
        }

        $folder = $this->get_folder($folder_id);
        if (!$folder) {
            return new WP_Error('permission_denied', 'You do not have permission to access this folder');
        }
        return $folder;
    }

    /**
     * Edits a folder's attributes
     *
     * @param int $folder_id The ID of the folder to edit
     * @param array $attributes Array of attributes to update
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function edit_folder($folder_id, $attributes) {
        $folder = $this->validate_folder_access($folder_id);
        if (is_wp_error($folder)) {
            return $folder;
        }

        $update_data = array();
        $update_format = array();

        if (isset($attributes['name'])) {
            $update_data['name'] = sanitize_text_field($attributes['name']);
            $update_format[] = '%s';
        }
        if (isset($attributes['parent_id'])) {
            $update_data['parent_id'] = intval($attributes['parent_id']);
            $update_format[] = '%d';
        }
        if (isset($attributes['team_id'])) {
            $update_data['team_id'] = intval($attributes['team_id']);
            $update_format[] = '%d';
        }

        if (empty($update_data)) {
            return new WP_Error('no_changes', 'No valid changes provided');
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $folder_id),
            $update_format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update folder: ' . $this->wpdb->last_error);
        }

        return true;
    }

    /**
     * Gets subfolders for a given parent folder
     *
     * @param int|string $parent_id The ID of the parent folder or 'root'
     * @param bool $recursive Whether to get subfolders recursively
     * @param bool $ids_only Whether to return only folder IDs instead of full folder data
     * @return array Array of folder data or folder IDs
     */
    private function get_subfolder_data($parent_id, $recursive = true, $ids_only = false) {
        // Return early if user is not logged in
        if (!is_user_logged_in()) {
            return [];
        }

        $select = $ids_only ? 'id' : '*';
        
        // Handle "root" case by looking for NULL parent_id
        $where_clause = $parent_id === "root"
            ? "(parent_id IS NULL OR parent_id = 0)"
            : "parent_id = %d";

        $query = $this->wpdb->prepare(
            "SELECT {$select} FROM {$this->table_name} 
             WHERE {$where_clause} AND (created_by = %d AND team_id = %d)",
            ...($parent_id === "root" 
                ? [$this->user_id, $this->team_id] 
                : [$parent_id, $this->user_id, $this->team_id])
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        if (!$recursive) {
            return $ids_only ? array_column($results, 'id') : $results;
        }

        $all_results = $results;
        foreach ($results as $row) {
            $child_results = $this->get_subfolder_data($row['id'], true, $ids_only);
            if (!empty($child_results)) {
                $all_results = array_merge($all_results, $child_results);
            }
        }

        return $ids_only ? array_unique(array_column($all_results, 'id')) : $all_results;
    }

    /**
     * Gets all subfolder IDs for a given parent folder
     *
     * @param int|string $parent_id The ID of the parent folder or 'root'
     * @param bool $recursive Whether to get subfolder IDs recursively
     * @return array Array of folder IDs
     */
    public function get_subfolder_ids($parent_id, $recursive = true) {
        return $this->get_subfolder_data($parent_id, $recursive, true);
    }

    /**
     * Gets all subfolders for a given parent folder
     *
     * @param int|string $parent_id The ID of the parent folder or 'root'
     * @param bool $recursive Whether to get subfolders recursively
     * @return array Array of folder data
     */
    public function get_subfolders($parent_id, $recursive = true) {
        return $this->get_subfolder_data($parent_id, $recursive, false);
    }

    public function get_folder($folder_id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND (created_by = %d AND team_id = %d)",
            $folder_id,
            $this->user_id,
            $this->team_id
        ), ARRAY_A);
    }

    /**
     * Deletes a folder and moves its subfolders to the parent folder
     *
     * @param int $folder_id The ID of the folder to delete
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function delete_folder($folder_id) {
        $folder = $this->validate_folder_access($folder_id);
        if (is_wp_error($folder)) {
            return $folder;
        }

        // Get the parent folder ID
        $parent_id = $folder['parent_id'];

        // Move all subfolders to the parent folder
        $subfolder_ids = $this->get_subfolder_ids($folder_id, false); // Only immediate subfolders
        if (!empty($subfolder_ids)) {
            $placeholders = implode(',', array_fill(0, count($subfolder_ids), '%d'));
            $query = $this->wpdb->prepare(
                "UPDATE {$this->table_name} SET parent_id = %d WHERE id IN ($placeholders)",
                array_merge([$parent_id], $subfolder_ids)
            );
            $this->wpdb->query($query);
        }

        // Delete the folder 
        $result = $this->wpdb->delete($this->table_name, array('id' => $folder_id), array('%d'));
        if ($result === false) {
            return new WP_Error('delete_failed', 'Failed to delete folder: ' . $this->wpdb->last_error);
        }

        return true;
    }



    public function move_folder($folder_id, $new_parent_id)
    {
        return $this->edit_folder($folder_id, array('parent_id' => $new_parent_id));
    }

    public function move_folders($folder_ids, $new_parent_id) {
        if (!is_array($folder_ids)) {
            return new WP_Error('invalid_input', 'Invalid input. Expected an array of folder IDs.');
        }
        $folder_ids = array_unique($folder_ids);
        // bulk move folders
        $placeholders = implode(',', array_fill(0, count($folder_ids), '%d'));
        $query = $this->wpdb->prepare(
            "UPDATE {$this->table_name} SET parent_id = %d WHERE id IN ($placeholders)",
            array_merge([$new_parent_id], $folder_ids)
        );
        $moveFolders = $this->wpdb->query($query);
        if ($moveFolders === false) {
            return new WP_Error('move_failed', 'Failed to move folders: ' . $this->wpdb->last_error);
        }
        return true;
        
    }

    public function get_folder_path($folder_id)
    {
        $path = array();
        $current_folder = $this->get_folder($folder_id);

        while ($current_folder) {
            array_unshift($path, $current_folder);
            if ($current_folder['parent_id']) {
                $current_folder = $this->get_folder($current_folder['parent_id']);
            } else {
                break;
            }
        }

        return $path;
    }

    public function get_templates_in_folder($folder_id, $recursive = false)
    {
        $template_manager = new WizardTemplates();
        return $template_manager->get_templates_by_folders($folder_id, [
            'recursive' => $recursive
        ]);
    }

    /**
     * Validates if the user has access to the specified team
     *
     * @param int $team_id The team ID to validate
     * @return bool|WP_Error True if user has access, WP_Error if not
     */
    private function validate_team_access($team_id) {
        if (!$team_id) {
            return new WP_Error('invalid_team', 'Invalid team ID');
        }

        $teams = new WizardTeams();
        $user_teams = $teams->get_user_teams($this->user_id);
        
        if (!in_array($team_id, $user_teams)) {
            return new WP_Error('permission_denied', 'You do not have permission to access this team\'s folders');
        }

        return true;
    }

    /**
     * Updates the team context for the folder manager
     *
     * @param int $team_id New team ID
     * @return bool|WP_Error True on success, WP_Error if user doesn't have access
     */
    public function switch_team($team_id) {
        $team_access = $this->validate_team_access($team_id);
        if (is_wp_error($team_access)) {
            return $team_access;
        }

        $this->team_id = $team_id;
        return true;
    }
}
