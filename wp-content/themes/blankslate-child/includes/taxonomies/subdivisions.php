<?php
/**
 * Custom post type: Subdivisions.
 *
 * The parts of a nation that have no ISO 3166-1 code of their own: states,
 * provinces and regions such as Hawaii, Oaxaca or Catalonia. Each one mirrors
 * an Airtable Territories record, keyed on its record ID, and is written only
 * by the Airtable sync (POST /wp-json/wikitongues/v1/sync/territories).
 * Territories that do have an ISO code (Guam, Greenland) are `territories`
 * posts instead.
 *
 * Not public: subdivisions never appear in menus, archives or search. Their
 * public view is a route nested under the nation, added with the routes work
 * in plan 6.8 phase B.
 *
 * @package Wikitongues
 */

add_action( 'init', 'wt_register_subdivisions_post_type' );

/**
 * Register the `subdivisions` post type.
 */
function wt_register_subdivisions_post_type() {
	register_post_type(
		'subdivisions',
		array(
			'labels'              => array(
				'name'               => __( 'Subdivisions', 'subdivisions' ),
				'singular_name'      => __( 'Subdivision', 'subdivisions' ),
				'edit_item'          => __( 'Edit Subdivision', 'subdivisions' ),
				'search_items'       => __( 'Search Subdivisions', 'subdivisions' ),
				'not_found'          => __( 'No Subdivisions found', 'subdivisions' ),
				'not_found_in_trash' => __( 'No Subdivisions found in Trash', 'subdivisions' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=territories',
			'show_in_rest'        => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'can_export'          => true,
		)
	);
}
