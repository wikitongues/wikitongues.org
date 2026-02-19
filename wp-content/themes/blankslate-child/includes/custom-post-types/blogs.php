<?php
add_action( 'init', 'create_post_type_blog' );
function create_post_type_blog() {
	register_post_type(
		'blog',
		array(
			'labels'       => array(
				'name'               => 'Blogs',
				'singular_name'      => 'Blog',
				'add_new'            => 'Add New Blog',
				'add_new_item'       => 'Add New Blog Post',
				'edit_item'          => 'Edit Blog Post',
				'new_item'           => 'New Blog Post',
				'view_item'          => 'View Blog Post',
				'search_items'       => 'Search Blog Posts',
				'not_found'          => 'No blog posts found',
				'not_found_in_trash' => 'No blog posts found in trash',
			),
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => 'blog' ),
			'menu_icon'    => 'dashicons-admin-post',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments' ),
			'show_in_rest' => true,
		)
	);
}
