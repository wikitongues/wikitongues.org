<?php
// Register Custom Post Type for Events
add_action( 'init', 'create_events_cpt' );
function create_events_cpt() {
	$labels = array(
		'name'           => __( 'Events', 'events' ),
		'singular_name'  => __( 'Event', 'events' ),
		'menu_name'      => __( 'Events', 'events' ),
		'name_admin_bar' => __( 'Event', 'events' ),
		'add_new'        => __( 'Add New', 'events' ),
		'add_new_item'   => __( 'Add New Event', 'events' ),
		'new_item'       => __( 'New Event', 'events' ),
		'edit_item'      => __( 'Edit Event', 'events' ),
		'view_item'      => __( 'View Event', 'events' ),
		'all_items'      => __( 'All Events', 'events' ),
		'search_items'   => __( 'Search Events', 'events' ),
	);

	$args = array(
		'labels'       => $labels,
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'events' ),
		'supports'     => array( 'title', 'revisions' ),
		'menu_icon'    => 'dashicons-calendar-alt',
		'show_in_rest' => true,
	);

	register_post_type( 'events', $args );
}
