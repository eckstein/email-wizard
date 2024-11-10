<?php
class WizardFolders
{
    private $wpdb;
    private $table_name;
    private $user_id;
    private $team_id;

    public function __construct($user_id, $team_id = null)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'user_folders';
        $this->user_id = $user_id;
        $this->team_id = $team_id;
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
        $query = "SELECT * FROM {$this->table_name} WHERE (created_by = %d OR team_id = %d) ";
        $params = [$this->user_id, $this->team_id];

        if (!empty($exclude)) {
            $placeholders = implode(',', array_fill(0, count($exclude), '%d'));
            $query .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $exclude);
        }

        return $this->wpdb->get_results($this->wpdb->prepare($query, $params), ARRAY_A);
    }
    
    public function edit_folder($folder_id, $attributes)
    {
        // Check if the folder belongs to the user or their team
        $folder = $this->get_folder($folder_id);
        if (!$folder) {
            return new WP_Error('permission_denied', 'You do not have permission to edit this folder');
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

    public function get_subfolder_ids($parent_id, $recursive = true)
    {
        $subfolder_ids = array();
        
        // Handle "root" case by looking for NULL parent_id
        $where_clause = $parent_id === "root" 
            ? "parent_id IS NULL OR parent_id = 0" 
            : "parent_id = %d";
        
        $query = $this->wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE {$where_clause} AND (created_by = %d OR team_id = %d) ",
            ...($parent_id === "root" ? [$this->user_id, $this->team_id] : [$parent_id, $this->user_id, $this->team_id])
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);

        foreach ($results as $row) {
            $subfolder_ids[] = $row['id'];
            if ($recursive) {
                $subfolder_ids = array_merge(
                    $subfolder_ids,
                    $this->get_subfolder_ids($row['id'], $recursive)
                );
            }
        }

        return array_unique($subfolder_ids);
    }

    public function get_subfolders($parent_id, $recursive = true) {
        $subfolders = array();

        // Handle "root" case by looking for NULL parent_id
        $where_clause = $parent_id === "root"
            ? "parent_id IS NULL OR parent_id = 0"
            : "parent_id = %d";

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_clause} AND (created_by = %d OR team_id = %d)",
            ...($parent_id === "root" ? [$this->user_id, $this->team_id] : [$parent_id, $this->user_id, $this->team_id])
        );
        $results = $this->wpdb->get_results($query, ARRAY_A);
        foreach ($results as $row) {
            $subfolders[] = $row;
            if ($recursive) {
                $subfolders = array_merge(
                    $subfolders,
                    $this->get_subfolders($row['id'], $recursive)
                );
            }
        }
        return $subfolders;
    }

    public function get_folder($folder_id)
    {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND (created_by = %d OR team_id = %d)",
            $folder_id,
            $this->user_id,
            $this->team_id
        ), ARRAY_A);
    }

    public function delete_folder($folder_id)
    {
        $folder = $this->get_folder($folder_id);
        if (!$folder) {
            return new WP_Error('permission_denied', 'You do not have permission to delete this folder');
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

    public function get_templates_in_folder($folder_id, $recursive = false) {
        // Get templates in current folder
        $args = array(
            'post_type' => 'wiz_template',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'wizard_folder',
                    'value' => $folder_id,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        $templates = $query->posts;

        // If recursive, get templates from all subfolders
        if ($recursive) {
            $subfolders = $this->get_subfolders($folder_id, true);
            foreach ($subfolders as $subfolder) {
                $subfolder_args = array(
                    'post_type' => 'wiz_template',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => 'wizard_folder',
                            'value' => $subfolder['id'],
                            'compare' => '='
                        )
                    )
                );
                
                $subfolder_query = new WP_Query($subfolder_args);
                $templates = array_merge($templates, $subfolder_query->posts);
            }
        }

        return $templates;
    }
}
