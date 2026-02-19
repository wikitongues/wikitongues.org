<?php
add_action( 'init', 'create_post_type_lexicons' );
function create_post_type_lexicons() {
	register_taxonomy_for_object_type( 'category', 'lexicons' );
	register_taxonomy_for_object_type( 'post_tag', 'lexicons' );
	register_post_type(
		'lexicons',
		array(
			'labels'                => array(
				'name'               => __( 'Lexicons', 'lexicons' ),
				'singular_name'      => __( 'Lexicon', 'lexicon' ),
				'add_new'            => __( 'Add New', 'lexicon' ),
				'add_new_item'       => __( 'Add New Lexicon', 'lexicon' ),
				'edit'               => __( 'Edit', 'lexicon' ),
				'edit_item'          => __( 'Edit Lexicon', 'lexicon' ),
				'new_item'           => __( 'New Lexicon', 'lexicon' ),
				'view'               => __( 'View Lexicon', 'lexicon' ),
				'view_item'          => __( 'View Lexicon', 'lexicon' ),
				'search_items'       => __( 'Search Lexicons', 'lexicon' ),
				'not_found'          => __( 'No Lexicons found', 'lexicon' ),
				'not_found_in_trash' => __( 'No Lexicon found in Trash', 'lexicon' ),
			),
			'public'                => true,
			'hierarchical'          => true,
			'menu_icon'             => 'dashicons-format-status',
			'has_archive'           => true,
			'supports'              => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
			),
			'can_export'            => true,
			'taxonomies'            => array(
				'post_tag',
				'category',
			),
			'show_in_rest'          => true,
			'rest_controller_class' => 'WT_REST_Posts_Controller',
		)
	);
}
