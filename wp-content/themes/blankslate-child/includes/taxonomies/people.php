<?php
/**
 * People CPT — the organisation's registry of humans.
 *
 * Renamed from `team`, which was named after one use of it. A person is now
 * typed by the `people-type` taxonomy (board member, staff, volunteer,
 * advisor, and donor once that work lands) and can hold several types at once,
 * so the same record can be both a board member and a donor.
 *
 * Reading a person's types: use get_the_terms( $id, 'people-type' ), not
 * get_field( 'people_type' ). The ACF field is a picker for the taxonomy, not a
 * separate store — it runs with load_terms and save_terms both on, and ACF
 * deliberately drops the meta value in that mode so the taxonomy stays the
 * single source of truth. get_field() therefore returns null by design.
 * Galleries filter on the taxonomy directly via tax_query.
 *
 * The post type is deliberately locked down: no REST route, excluded from
 * search, and no archive. Nothing on the site reads people over REST or search
 * — the site search is scoped to languages and videos, and the typeahead to
 * languages — so this costs nothing today and keeps donor records from being
 * publicly enumerable once they exist.
 *
 * @package Wikitongues
 */

add_action( 'init', 'create_post_type_people' );
function create_post_type_people() {
	register_post_type(
		'people',
		array(
			'labels'              => array(
				'name'               => __( 'People', 'people' ),
				'singular_name'      => __( 'Person', 'people' ),
				'add_new'            => __( 'Add New', 'people' ),
				'add_new_item'       => __( 'Add New Person', 'people' ),
				'edit'               => __( 'Edit', 'people' ),
				'edit_item'          => __( 'Edit Person', 'people' ),
				'new_item'           => __( 'New Person', 'people' ),
				'view'               => __( 'View Person', 'people' ),
				'view_item'          => __( 'View Person', 'people' ),
				'search_items'       => __( 'Search People', 'people' ),
				'not_found'          => __( 'No people found', 'people' ),
				'not_found_in_trash' => __( 'No people found in Trash', 'people' ),
			),
			'public'              => true,
			// Singles stay queryable so the redirect in router.php still fires,
			// but people are kept out of search results and the REST API.
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'hierarchical'        => true,
			'menu_icon'           => 'dashicons-groups',
			'rewrite'             => array( 'slug' => 'people' ),
			'supports'            => array(
				'title',
				'thumbnail',
			),
			'can_export'          => true,
		)
	);
}

add_action( 'init', 'register_people_type_taxonomy' );
function register_people_type_taxonomy() {
	register_taxonomy(
		'people-type',
		'people',
		array(
			'labels'            => array(
				'name'          => __( 'People Types', 'people' ),
				'singular_name' => __( 'People Type', 'people' ),
				'search_items'  => __( 'Search People Types', 'people' ),
				'all_items'     => __( 'All People Types', 'people' ),
				'edit_item'     => __( 'Edit People Type', 'people' ),
				'update_item'   => __( 'Update People Type', 'people' ),
				'add_new_item'  => __( 'Add New People Type', 'people' ),
				'new_item_name' => __( 'New People Type Name', 'people' ),
				'menu_name'     => __( 'People Types', 'people' ),
			),
			'hierarchical'      => false,
			// Terms drive page composition, not public archives. Keeping the
			// taxonomy non-public means there is no /people-type/donor/ page
			// listing donors, and no REST route enumerating them.
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => false,
			'query_var'         => false,
			'rewrite'           => false,
		)
	);
}
