<?php
add_action( 'init', 'create_post_type_careers' );
function create_post_type_careers() {
	$labels = array(
		'name'               => __( 'Careers', 'careers' ),
		'singular_name'      => __( 'Career', 'careers' ),
		'add_new'            => __( 'Add New', 'careers' ),
		'add_new_item'       => __( 'Add New Career', 'careers' ),
		'edit_item'          => __( 'Edit Career', 'careers' ),
		'new_item'           => __( 'New Career', 'careers' ),
		'view_item'          => __( 'View Career', 'careers' ),
		'search_items'       => __( 'Search Careers', 'careers' ),
		'not_found'          => __( 'No careers found', 'careers' ),
		'not_found_in_trash' => __( 'No careers found in Trash', 'careers' ),
		'menu_name'          => __( 'Careers', 'careers' ),
	);

	$args = array(
		'labels'       => $labels,
		'public'       => true,
		'has_archive'  => false,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'supports'     => array( 'title' ),
		'rewrite'      => array( 'slug' => 'careers' ),
		'menu_icon'    => 'dashicons-businessperson',
	);

	register_post_type( 'careers', $args );
}

add_action( 'init', 'register_careers_taxonomy' );
function register_careers_taxonomy() {
	$labels = array(
		'name'          => __( 'Career Types', 'careers' ),
		'singular_name' => __( 'Career Type', 'careers' ),
		'search_items'  => __( 'Search Career Types', 'careers' ),
		'all_items'     => __( 'All Career Types', 'careers' ),
		'edit_item'     => __( 'Edit Career Type', 'careers' ),
		'update_item'   => __( 'Update Career Type', 'careers' ),
		'add_new_item'  => __( 'Add New Career Type', 'careers' ),
		'new_item_name' => __( 'New Career Type Name', 'careers' ),
		'menu_name'     => __( 'Career Types', 'careers' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'career-type' ),
	);

	register_taxonomy( 'career_type', 'careers', $args );
}
