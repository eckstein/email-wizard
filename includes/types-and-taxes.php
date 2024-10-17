<?php
// Register Custom Post Type
function init_wizard_post_types() {

	$labels = array(
		'name' => _x( 'Templates', 'Post Type General Name', 'emailwizard' ),
		'singular_name' => _x( 'Template', 'Post Type Singular Name', 'emailwizard' ),
		'menu_name' => __( 'Templates', 'emailwizard' ),
		'name_admin_bar' => __( 'Template', 'emailwizard' ),
		'archives' => __( 'Template Archive', 'emailwizard' ),
		'attributes' => __( 'Template Attributes', 'emailwizard' ),
		'parent_item_colon' => __( 'Parent Template:', 'emailwizard' ),
		'all_items' => __( 'All Templates', 'emailwizard' ),
		'add_new_item' => __( 'Add New Templates', 'emailwizard' ),
		'add_new' => __( 'Add New', 'emailwizard' ),
		'new_item' => __( 'New Template', 'emailwizard' ),
		'edit_item' => __( 'Edit Template', 'emailwizard' ),
		'update_item' => __( 'Update Template', 'emailwizard' ),
		'view_item' => __( 'View Template', 'emailwizard' ),
		'view_items' => __( 'View Templates', 'emailwizard' ),
		'search_items' => __( 'Search Templates', 'emailwizard' ),
		'not_found' => __( 'Not found', 'emailwizard' ),
		'not_found_in_trash' => __( 'Not found in Trash', 'emailwizard' ),
		'featured_image' => __( 'Featured Image', 'emailwizard' ),
		'set_featured_image' => __( 'Set featured image', 'emailwizard' ),
		'remove_featured_image' => __( 'Remove featured image', 'emailwizard' ),
		'use_featured_image' => __( 'Use as featured image', 'emailwizard' ),
		'insert_into_item' => __( 'Insert into template', 'emailwizard' ),
		'uploaded_to_this_item' => __( 'Uploaded to this template', 'emailwizard' ),
		'items_list' => __( 'Templates list', 'emailwizard' ),
		'items_list_navigation' => __( 'Templates list navigation', 'emailwizard' ),
		'filter_items_list' => __( 'Filter templates list', 'emailwizard' ),
	);
	$rewrite = array(
		'slug' => 'template',
		'with_front' => false,
		'pages' => true,
		'feeds' => true,
	);
	$args = array(
		'label' => __( 'Template', 'emailwizard' ),
		'description' => __( 'Email Wizard Templates', 'emailwizard' ),
		'labels' => $labels,
		'supports' => array( 'title', 'editor', 'custom-fields' ),
		'hierarchical' => false,
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 3,
		'menu_icon' => 'dashicons-media-code',
		'show_in_admin_bar' => true,
		'show_in_nav_menus' => false,
		'can_export' => false,
		'has_archive' => 'templates',
		'exclude_from_search' => false,
		'publicly_queryable' => true,
		'rewrite' => $rewrite,
		'capability_type' => 'post',
		'show_in_rest' => true,
	);
	register_post_type( 'wiz_template', $args );

}
add_action( 'init', 'init_wizard_post_types', 0 );

// Register Custom Taxonomy
function init_wizard_taxonomies() {

	// Nothing here yet (or never?)

}
add_action( 'init', 'init_wizard_taxonomies', 0 );