<?php
class WizardTemplates
{
	const POST_TYPE = 'wiz_template';
	const ROOT_FOLDER = 'root';
	const TRASH_FOLDER = 'trash';

	private $wpdb;
	private $template_data_table;
	private $user_id;
	private $team_id;
	private $folder_manager;
	private static $instance = null;

	public function __construct()
	{
		// Check if user is logged in
		if (!is_user_logged_in()) {
			return;
		}

		global $wpdb;
		$this->wpdb = $wpdb;
		$this->template_data_table = $wpdb->prefix . 'wiz_templates';
		$this->user_id = get_current_user_id();
		$teamManager = new WizardTeams();
		$this->team_id = $teamManager->get_active_team($this->user_id);
		
		// Only initialize folder manager if we have a team
		if ($this->team_id) {
			$this->folder_manager = new WizardFolders($this->user_id, $this->team_id);
		}
	}

	public static function get_instance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_template($post_id)
	{
		$post = $this->validate_template($post_id);
		return $post ? $this->enrich_template_post($post) : null;
	}

	public function get_templates($folder_id = null, $args = array()) {
		$default_args = array(
			'post_type'      => 'wiz_template',
			'posts_per_page' => get_option('posts_per_page'),
			'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
			'orderby'        => 'title',
			'order'          => 'ASC'
		);

		// Add team filtering
		if (!current_user_can('manage_options')) {
			$user_teams = get_user_meta(get_current_user_id(), 'user_teams', true);
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => 'template_teams',
					'value'   => $user_teams,
					'compare' => 'IN',
				)
			);
		}

		// Merge with existing folder filtering if present
		if ($folder_id !== null) {
			$folder_meta_query = array(
				'key'   => 'template_folder_id',
				'value' => $folder_id
			);
			
			if (isset($args['meta_query'])) {
				$args['meta_query'][] = $folder_meta_query;
			} else {
				$args['meta_query'] = array($folder_meta_query);
			}
		}

		// Handle trash folder
		if ($folder_id === 'trash') {
			$args['post_status'] = 'trash';
		} else {
			$args['post_status'] = 'publish';
		}

		// Create and return WP_Query
		return new WP_Query($args);
	}

	public function enrich_template_post($post)
	{
		if (!$post) return null;

		$template_data_id = get_post_meta($post->ID, 'template_data_id', true);
		$template_data = null;

		if ($template_data_id) {
			$template_data = $this->wpdb->get_var($this->wpdb->prepare(
				"SELECT template_data FROM {$this->template_data_table} WHERE id = %d",
				$template_data_id
			));
			$template_data = $template_data ? json_decode($template_data, true) : null;
		}

		return [
			'id' => $post->ID,
			'title' => $post->post_title,
			'folder_id' => get_post_meta($post->ID, 'wizard_folder', true),
			'team_id' => get_post_meta($post->ID, 'wizard_team', true),
			'author_id' => $post->post_author,
			'template_data' => $template_data,
			'created_at' => $post->post_date,
			'updated_at' => $post->post_modified,
			'original_post' => $post
		];
	}

	public function create_template($template_name = 'untitled', $folder_id = self::ROOT_FOLDER, $args = [])
	{
		if (!is_array($args)) {
			$args = [];
		}

		$args = wp_parse_args($args, [
			'post_title' => $template_name,
			'post_type' => self::POST_TYPE,
			'post_status' => 'publish',
			'post_author' => $this->user_id
		]);

		$post_id = wp_insert_post($args);
		if (is_wp_error($post_id)) {
			return $post_id;
		}

		update_post_meta($post_id, 'wizard_folder', $folder_id);
		update_post_meta($post_id, 'wizard_team', $this->team_id);

		// Create empty template data
		$this->wpdb->insert(
			$this->template_data_table,
			[
				'post_id' => $post_id,
				'template_data' => json_encode([])
			]
		);

		update_post_meta($post_id, 'template_data_id', $this->wpdb->insert_id);

		return $post_id;
	}

	public function duplicate_template($template_id)
	{
		$template = get_post($template_id);
		if (!$template || $template->post_type !== self::POST_TYPE) {
			return null;
		}

		$new_post_id = wp_insert_post([
			'post_title' => '(Copy) ' . $template->post_title,
			'post_status' => 'publish',
			'post_type' => self::POST_TYPE,
			'post_author' => $this->user_id
		]);

		// Copy meta data
		$folder_id = get_post_meta($template_id, 'wizard_folder', true);
		update_post_meta($new_post_id, 'wizard_folder', $folder_id);
		update_post_meta($new_post_id, 'wizard_team', $this->team_id);

		// Duplicate template data
		$original_template_data_id = get_post_meta($template_id, 'template_data_id', true);
		if ($original_template_data_id) {
			$template_data = $this->wpdb->get_var($this->wpdb->prepare(
				"SELECT template_data FROM {$this->template_data_table} WHERE id = %d",
				$original_template_data_id
			));

			$this->wpdb->insert(
				$this->template_data_table,
				[
					'post_id' => $new_post_id,
					'template_data' => $template_data
				]
			);

			update_post_meta($new_post_id, 'template_data_id', $this->wpdb->insert_id);
		}

		return $new_post_id;
	}

	public function search_templates($term, $folderIds = ['root'])
	{
		$query = new WP_Query([
			'post_type' => self::POST_TYPE,
			'post_status' => 'publish',
			's' => $term,
			'meta_query' => [
				'relation' => 'AND',
				[
					'key' => 'wizard_folder',
					'value' => $folderIds,
					'compare' => 'IN'
				],
				[
					'key' => 'wizard_team',
					'value' => $this->team_id
				]
			]
		]);

		return array_map([$this, 'enrich_template_post'], $query->posts);
	}

	/**
	 * Get templates from specified folders
	 * 
	 * @param string|array $folder_ids Single folder ID or array of folder IDs
	 * @param array $args {
	 *     Optional. Arguments to modify the query.
	 *     @type bool   $recursive      Whether to include templates from subfolders
	 *     @type string $orderby        How to sort the results
	 *     @type string $order          Sort direction (ASC or DESC)
	 * }
	 * @return array Array of enriched template data
	 */
	public function get_templates_by_folders($folder_ids, $args = [])
	{
		$folder_ids = (array)$folder_ids;
		$args = wp_parse_args($args, [
			'recursive' => false,
			'orderby' => 'title',
			'order' => 'ASC',
		]);

		if ($args['recursive']) {
			$folder_ids = $this->get_recursive_folder_ids($folder_ids);
		}

		$meta_query = [
			'relation' => 'AND',
			[
				'key' => 'wizard_team',
				'value' => $this->team_id,
			],
			[
				'key' => 'wizard_folder',
				'value' => $folder_ids,
				'compare' => 'IN'
			]
		];

		$query_args = [
			'post_type' => self::POST_TYPE,
			'posts_per_page' => -1,
			'meta_query' => $meta_query,
			'orderby' => $args['orderby'],
			'order' => $args['order'],
		];

		// Only add search logic if search_term exists and is not empty
		if (isset($args['search_term']) && trim($args['search_term']) !== '') {
			$this->add_search_filter($args['search_term']);
			// ... execute query ...
			remove_all_filters('posts_where');
		}

		$query = new WP_Query($query_args);

		// Remove our filter if we added it
		if (isset($args['search_term']) && trim($args['search_term']) !== '') {
			remove_all_filters('posts_where');
		}

		return array_map([$this, 'enrich_template_post'], $query->posts);
	}

	/**
	 * Helper method to get all subfolder IDs recursively
	 */
	private function get_recursive_folder_ids($folder_ids)
	{
		$all_folder_ids = (array)$folder_ids;

		foreach ($folder_ids as $folder_id) {
			$subfolders = $this->folder_manager->get_subfolders($folder_id, true);
			if (!empty($subfolders)) {
				$subfolder_ids = array_column($subfolders, 'id');
				$all_folder_ids = array_merge($all_folder_ids, $subfolder_ids);
			}
		}

		return array_unique($all_folder_ids);
	}

	// Helper method to get template data
	public function get_template_data($post_id)
	{
		$template_data_id = get_post_meta($post_id, 'template_data_id', true);
		if (!$template_data_id) return null;

		$template_data = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT template_data FROM {$this->template_data_table} WHERE id = %d",
			$template_data_id
		));

		return $template_data ? json_decode($template_data, true) : null;
	}

	/**
	 * Handle template being moved to trash
	 * 
	 * @param int $post_id The template post ID
	 */
	public static function handle_template_trashed($post_id) {
		if (get_post_type($post_id) !== self::POST_TYPE) {
			return;
		}
		
		// Store the current folder before trashing
		$current_folder = get_post_meta($post_id, 'wizard_folder', true);
		update_post_meta($post_id, 'wizard_folder_before_trash', $current_folder);
		update_post_meta($post_id, 'wizard_folder', self::TRASH_FOLDER);
	}

	/**
	 * Handle template being restored from trash
	 * 
	 * @param int $post_id The template post ID
	 */
	public static function handle_template_restored($post_id) {
		if (get_post_type($post_id) !== self::POST_TYPE) {
			return;
		}

		// Restore the template to its previous folder
		$previous_folder = get_post_meta($post_id, 'wizard_folder_before_trash', true);
		$folder = $previous_folder ? $previous_folder : self::ROOT_FOLDER;
		update_post_meta($post_id, 'wizard_folder', $folder);
		delete_post_meta($post_id, 'wizard_folder_before_trash');
		// Publish the template
		wp_publish_post($post_id);
	}

	/**
	 * Builds common WP_Query arguments for templates
	 *
	 * @param array $additional_args Additional query arguments
	 * @return array Complete query arguments
	 */
	private function build_template_query_args($additional_args = []) {
		$base_args = [
			'post_type' => self::POST_TYPE,
			'meta_query' => [
				[
					'key' => 'wizard_team',
					'value' => $this->team_id
				]
			]
		];
		
		return wp_parse_args($additional_args, $base_args);
	}

	/**
	 * Adds search conditions to the WP_Query
	 *
	 * @param string $search_term The term to search for
	 * @return void
	 */
	private function add_search_filter($search_term) {
		add_filter('posts_where', function($where) use ($search_term) {
			global $wpdb;
			$search_term = '%' . $wpdb->esc_like($search_term) . '%';
			$where .= $wpdb->prepare(
				" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)",
				$search_term,
				$search_term
			);
			return $where;
		});
	}

	/**
	 * Validates a template post and checks permissions
	 *
	 * @param int|WP_Post $post Post ID or post object
	 * @return WP_Post|null Post object if valid, null otherwise
	 */
	private function validate_template($post) {
		if (!is_user_logged_in() || !$this->team_id) {
			return null;
		}

		if (is_numeric($post)) {
			$post = get_post($post);
		}
		
		if (!$post || $post->post_type !== self::POST_TYPE) {
			return null;
		}

		// Check if user has access to this team's template
		$team_id = get_post_meta($post->ID, 'wizard_team', true);
		if ($team_id != $this->team_id) {
			return null;
		}

		return $post;
	}
}

// Add the hooks in the main plugin file or a suitable initialization point
add_action('wp_trash_post', [WizardTemplates::class, 'handle_template_trashed']);
add_action('untrashed_post', [WizardTemplates::class, 'handle_template_restored']);
