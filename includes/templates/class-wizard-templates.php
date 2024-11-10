<?php
class WizardTemplates {
	private $wpdb;
	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = $wpdb->prefix . 'wiz_templates';
	}

	public function get_template($postId) {
		return $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE post_id = %d",
			$postId
		), ARRAY_A);
	}

	public function create_template($template_name = 'untitled', $folder_id = 'root', $args = []) {
		if (!is_array($args)) {
			$args = [];
		}
		$args['post_title'] = $template_name;
		$args['post_type'] = 'wiz_template';
		$args['post_status'] = 'publish';
		
		$new_template = wp_insert_post($args);
		if (is_wp_error($new_template)) {
			return $new_template;
		}
		
		update_post_meta($new_template, 'wizard_folder', $folder_id);
		return $new_template;
	}

	public function duplicate_template($template_id) {
		$template = get_post($template_id);
		$new_template = wp_insert_post(array(
			'post_title' => '(Copy) ' . $template->post_title,
			'post_status' => 'publish',
			'post_type' => 'wiz_template',
		));

		$folder = get_post_meta($template_id, 'wizard_folder', true);
		update_post_meta($new_template, 'wizard_folder', $folder);

		$template_data = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT template_data FROM {$this->table_name} WHERE post_id = %d",
			$template_id
		));
		
		$this->wpdb->update(
			$this->table_name,
			array('template_data' => $template_data),
			array('post_id' => $new_template)
		);

		return $new_template;
	}

	public function search_templates($term, $folderIds = ['root']) {
		$query = new WP_Query(array(
			'post_type' => 'wiz_template',
			'post_status' => 'publish',
			's' => $term,
			'meta_query' => array(
				array(
					'key' => 'wizard_folder',
					'value' => $folderIds,
					'compare' => 'IN'
				)
			)
		));
		return $query->get_posts();
	}
}