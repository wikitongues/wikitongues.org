<?php
add_action( 'init', 'create_post_type_partners' );
function create_post_type_partners() {
	register_taxonomy_for_object_type( 'category', 'partners' );
	register_taxonomy_for_object_type( 'post_tag', 'partners' );
	register_post_type(
		'partners',
		array(
			'labels'                => array(
				'name'               => __( 'Partners', 'partner' ),
				'singular_name'      => __( 'Partner', 'partner' ),
				'add_new'            => __( 'Add New', 'partner' ),
				'add_new_item'       => __( 'Add New Partner', 'partner' ),
				'edit'               => __( 'Edit', 'partner' ),
				'edit_item'          => __( 'Edit Partner', 'partner' ),
				'new_item'           => __( 'New Partner', 'partner' ),
				'view'               => __( 'View Partner', 'partner' ),
				'view_item'          => __( 'View Partner', 'partner' ),
				'search_items'       => __( 'Search Partners', 'partner' ),
				'not_found'          => __( 'No Partners found', 'partner' ),
				'not_found_in_trash' => __( 'No Partners found in Trash', 'partner' ),
			),
			'public'                => true,
			'hierarchical'          => true,
			'menu_icon'             => 'dashicons-heart',
			'has_archive'           => true,
			'supports'              => array(
				'title',
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

add_filter( 'manage_edit-partners_sortable_columns', 'make_partners_columns_sortable' );
function make_partners_columns_sortable( $columns ) {
	$columns['categories'] = 'categories';
	return $columns;
}

// Shortcode to Display FAQs
add_shortcode( 'partners', 'partner_shortcode' );
function partner_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'category' => '',
			'ids'      => '',
		),
		$atts,
		'partners'
	);

	$args = array(
		'post_type'      => 'partner',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	);

	if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'partner_category',
					'field'    => 'slug',
					'terms'    => explode( ',', $atts['category'] ),
				),
			);
	}

	if ( ! empty( $atts['ids'] ) ) {
			$args['post__in'] = explode( ',', $atts['ids'] );
	}

	$partners = new WP_Query( $args );

	ob_start();

	if ( $partners->have_posts() ) {
			echo '<section class="partners">';
			echo '<ul>';
		while ( $partners->have_posts() ) {
				$partners->the_post();
				echo '<li class="partner">';
				echo '<a href="' . $partner_link . '"><img src="' . $partner_logo . '" title="' . $partner_name . '" alt="' . $partner_name . '"></a>';
				// echo '<h3 class="partner-question">' . get_the_title() . '</h3>';
				// echo '<div class="partner-answer">' . apply_filters('the_content', get_the_content()) . '</div>';
				echo '</li>';
		}
			echo '</ul>';
			echo '</section>';
	}

	wp_reset_postdata();

	return ob_get_clean();
}
