<?php
add_action( 'init', 'create_post_type_blog' );
function create_post_type_blog() {
	register_post_type(
		'blog',
		array(
			'labels'       => array(
				'name'               => __( 'Blogs', 'blog' ),
				'singular_name'      => __( 'Blog', 'blog' ),
				'add_new'            => __( 'Add New Blog', 'blog' ),
				'add_new_item'       => __( 'Add New Blog Post', 'blog' ),
				'edit_item'          => __( 'Edit Blog Post', 'blog' ),
				'new_item'           => __( 'New Blog Post', 'blog' ),
				'view_item'          => __( 'View Blog Post', 'blog' ),
				'search_items'       => __( 'Search Blog Posts', 'blog' ),
				'not_found'          => __( 'No blog posts found', 'blog' ),
				'not_found_in_trash' => __( 'No blog posts found in trash', 'blog' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'can_export'   => true,
			'rewrite'      => array( 'slug' => 'blog' ),
			'menu_icon'    => 'dashicons-admin-post',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments' ),
			'show_in_rest' => true,
		)
	);
}
