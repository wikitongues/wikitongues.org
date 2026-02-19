<?php
add_action( 'init', 'create_post_type_reports' );
function create_post_type_reports() {
	register_taxonomy_for_object_type( 'category', 'reports' );
	register_taxonomy_for_object_type( 'post_tag', 'reports' );
	register_post_type(
		'reports',
		array(
			'labels'       => array(
				'name'               => __( 'Reports', 'reports' ),
				'singular_name'      => __( 'Report', 'reports' ),
				'add_new'            => __( 'Add New', 'reports' ),
				'add_new_item'       => __( 'Add New Report', 'reports' ),
				'edit'               => __( 'Edit', 'reports' ),
				'edit_item'          => __( 'Edit Report', 'reports' ),
				'new_item'           => __( 'New Report', 'reports' ),
				'view'               => __( 'View Report', 'reports' ),
				'view_item'          => __( 'View Report', 'reports' ),
				'search_items'       => __( 'Search Reports', 'reports' ),
				'not_found'          => __( 'No reports found', 'reports' ),
				'not_found_in_trash' => __( 'No reports found in Trash', 'reports' ),
			),
			'public'       => true,
			'hierarchical' => true,
			'menu_icon'    => 'dashicons-clipboard',
			'has_archive'  => true,
			'supports'     => array(
				'title',
			),
			'can_export'   => true,
			'taxonomies'   => array(
				'post_tag',
				'category',
			),
		)
	);
}
