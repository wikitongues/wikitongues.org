<?php
get_header();

// Grab the current region term.
$current_region    = get_queried_object();
$current_parent_id = $current_region->parent ?: $current_region->term_id;
$territory         = wt_prefix_the( $current_region->name );
$is_continent      = ( 0 === $current_region->parent );

// On continent pages, expand the query to include all child sub-region terms
// so the territory list and gallery are not empty.
$query_terms = array( $current_region->term_id );
if ( $is_continent ) {
	$child_terms = get_terms(
		array(
			'taxonomy'   => 'region',
			'parent'     => $current_region->term_id,
			'fields'     => 'ids',
			'hide_empty' => false,
		)
	);
	if ( ! is_wp_error( $child_terms ) && ! empty( $child_terms ) ) {
		$query_terms = array_merge( $query_terms, $child_terms );
	}
}

$territory_query = new WP_Query(
	array(
		'post_type'      => 'territories',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'tax_query'      => array(
			array(
				'taxonomy' => 'region',
				'field'    => 'term_id',
				'terms'    => $query_terms,
				'operator' => 'IN',
			),
		),
	)
);

echo '<div class="wt_meta--territories-single">';
echo '<h1>' . esc_html( $territory ) . '</h1>';
$current_region->parent !== 0 ? require 'modules/territories/territories-child-regions.php' : '';
require 'modules/territories/territories-sibling-regions.php';
require 'modules/territories/territories-parent-regions.php';
echo '</div>';

if ( $territory_query->have_posts() ) :
	// On continent pages the gallery's taxonomy/term params can only target a single
	// term slug, so pass the full territory ID list as selected_posts instead.
	$selected_posts   = '';
	$gallery_taxonomy = 'region';
	$gallery_term     = $current_region->slug;
	if ( $is_continent ) {
		$selected_posts   = implode( ',', wp_list_pluck( $territory_query->posts, 'ID' ) );
		$gallery_taxonomy = '';
		$gallery_term     = '';
	}

	echo '<div class="container">';
	$params = array(
		'title'          => 'Territories in ' . $territory,
		'subtitle'       => '',
		'show_total'     => 'true',
		'post_type'      => 'territories',
		'custom_class'   => '',
		'columns'        => 3,
		'posts_per_page' => 9,
		'orderby'        => 'title',
		'order'          => 'asc',
		'pagination'     => 'true',
		'meta_key'       => '',
		'meta_value'     => '',
		'selected_posts' => $selected_posts,
		'display_blank'  => 'true',
		'exclude_self'   => 'false',
		'taxonomy'       => $gallery_taxonomy,
		'term'           => $gallery_term,
	);
	echo create_gallery_instance( $params );

	wp_reset_postdata();
	echo '</div>';
endif;

get_footer();
