<?php
get_header();

$territory_slug         = isset( $_GET['territory'] ) ? sanitize_title( wp_unslash( $_GET['territory'] ) ) : '';
$territory_post         = $territory_slug ? get_page_by_path( $territory_slug, OBJECT, 'territories' ) : null;
$region_slug            = isset( $_GET['region'] ) ? sanitize_title( wp_unslash( $_GET['region'] ) ) : '';
$region_term            = $region_slug ? get_term_by( 'slug', $region_slug, 'region' ) : null;
$archive_columns        = 5;
$archive_posts_per_page = 100;

echo '<main class="wt_archive-fellows">';

if ( $territory_post ) {
	$territory_id   = $territory_post->ID;
	$territory_name = wt_prefix_the( $territory_post->post_title );

	$fellows_query = new WP_Query(
		array(
			'post_type'      => 'fellows',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => 'fellow_territory',
					'value'   => '"' . intval( $territory_id ) . '"',
					'compare' => 'LIKE',
				),
			),
		)
	);

	if ( $fellows_query->have_posts() ) {
		$fellow_ids = wp_list_pluck( $fellows_query->posts, 'ID' );
		wp_reset_postdata();
		$params = array(
			'title'          => 'Fellows from ' . $territory_name,
			'subtitle'       => '',
			'show_total'     => 'true',
			'post_type'      => 'fellows',
			'columns'        => $archive_columns,
			'posts_per_page' => $archive_posts_per_page,
			'orderby'        => 'title',
			'order'          => 'asc',
			'pagination'     => 'true',
			'meta_key'       => '',
			'meta_value'     => '',
			'selected_posts' => implode( ',', $fellow_ids ),
			'display_blank'  => 'false',
			'exclude_self'   => 'false',
			'taxonomy'       => '',
			'term'           => '',
			'link_out'       => '',
		);
	} else {
		$params = array(
			'title'          => 'Fellows from ' . $territory_name,
			'subtitle'       => '',
			'show_total'     => 'true',
			'post_type'      => 'fellows',
			'columns'        => $archive_columns,
			'posts_per_page' => $archive_posts_per_page,
			'orderby'        => 'title',
			'order'          => 'asc',
			'pagination'     => 'true',
			'meta_key'       => '',
			'meta_value'     => '',
			'selected_posts' => '-1',
			'display_blank'  => 'true',
			'exclude_self'   => 'false',
			'taxonomy'       => '',
			'term'           => '',
			'link_out'       => '',
		);
	}
	echo create_gallery_instance( $params );
} elseif ( $region_term ) {
	$region_name  = wt_prefix_the( $region_term->name );
	$is_continent = ( 0 === $region_term->parent );
	$query_terms  = array( $region_term->term_id );
	if ( $is_continent ) {
		$child_terms = get_terms(
			array(
				'taxonomy'   => 'region',
				'parent'     => $region_term->term_id,
				'fields'     => 'ids',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $child_terms ) && ! empty( $child_terms ) ) {
			$query_terms = array_merge( $query_terms, $child_terms );
		}
	}
	$region_territory_query = new WP_Query(
		array(
			'post_type'      => 'territories',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
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
	if ( $region_territory_query->have_posts() ) {
		$territory_ids = wp_list_pluck( $region_territory_query->posts, 'ID' );
		wp_reset_postdata();
		$fellows_meta_query = array( 'relation' => 'OR' );
		foreach ( $territory_ids as $t_id ) {
			$fellows_meta_query[] = array(
				'key'     => 'fellow_territory',
				'value'   => '"' . intval( $t_id ) . '"',
				'compare' => 'LIKE',
			);
		}
		$region_fellows_query = new WP_Query(
			array(
				'post_type'      => 'fellows',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => $fellows_meta_query,
			)
		);
		if ( $region_fellows_query->have_posts() ) {
			$fellow_ids = wp_list_pluck( $region_fellows_query->posts, 'ID' );
			wp_reset_postdata();
			$params = array(
				'title'          => 'Fellows from ' . $region_name,
				'subtitle'       => '',
				'show_total'     => 'true',
				'post_type'      => 'fellows',
				'columns'        => $archive_columns,
				'posts_per_page' => $archive_posts_per_page,
				'orderby'        => 'title',
				'order'          => 'asc',
				'pagination'     => 'true',
				'meta_key'       => '',
				'meta_value'     => '',
				'selected_posts' => implode( ',', $fellow_ids ),
				'display_blank'  => 'false',
				'exclude_self'   => 'false',
				'taxonomy'       => '',
				'term'           => '',
				'link_out'       => '',
			);
		} else {
			wp_reset_postdata();
			$params = array(
				'title'          => 'Fellows from ' . $region_name,
				'subtitle'       => '',
				'show_total'     => 'true',
				'post_type'      => 'fellows',
				'columns'        => $archive_columns,
				'posts_per_page' => $archive_posts_per_page,
				'orderby'        => 'title',
				'order'          => 'asc',
				'pagination'     => 'true',
				'meta_key'       => '',
				'meta_value'     => '',
				'selected_posts' => '-1',
				'display_blank'  => 'true',
				'exclude_self'   => 'false',
				'taxonomy'       => '',
				'term'           => '',
				'link_out'       => '',
			);
		}
	} else {
		wp_reset_postdata();
		$params = array(
			'title'          => 'Fellows from ' . $region_name,
			'subtitle'       => '',
			'show_total'     => 'true',
			'post_type'      => 'fellows',
			'columns'        => $archive_columns,
			'posts_per_page' => $archive_posts_per_page,
			'orderby'        => 'title',
			'order'          => 'asc',
			'pagination'     => 'true',
			'meta_key'       => '',
			'meta_value'     => '',
			'selected_posts' => '-1',
			'display_blank'  => 'true',
			'exclude_self'   => 'false',
			'taxonomy'       => '',
			'term'           => '',
			'link_out'       => '',
		);
	}
	echo create_gallery_instance( $params );
} else {
	$params = array(
		'title'          => 'Fellows',
		'subtitle'       => '',
		'show_total'     => 'true',
		'post_type'      => 'fellows',
		'columns'        => $archive_columns,
		'posts_per_page' => $archive_posts_per_page,
		'orderby'        => 'title',
		'order'          => 'asc',
		'pagination'     => 'true',
		'meta_key'       => '',
		'meta_value'     => '',
		'selected_posts' => '',
		'display_blank'  => 'false',
		'exclude_self'   => 'false',
		'taxonomy'       => '',
		'term'           => '',
		'link_out'       => home_url( '/revitalization/fellows', 'relative' ),
	);
	echo create_gallery_instance( $params );
}

echo '</main>';
require 'modules/newsletter.php';
get_footer();
