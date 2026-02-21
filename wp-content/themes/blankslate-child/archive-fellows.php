<?php
get_header();

$territory_slug = isset( $_GET['territory'] ) ? sanitize_title( wp_unslash( $_GET['territory'] ) ) : '';
$territory_post = $territory_slug ? get_page_by_path( $territory_slug, OBJECT, 'territories' ) : null;

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
			'columns'        => 3,
			'posts_per_page' => 9,
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
		);
	} else {
		$params = array(
			'title'          => 'Fellows from ' . $territory_name,
			'subtitle'       => '',
			'show_total'     => 'true',
			'post_type'      => 'fellows',
			'columns'        => 3,
			'posts_per_page' => 9,
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
		);
	}
	echo create_gallery_instance( $params );
} else {
	$params = array(
		'title'          => 'Fellows',
		'subtitle'       => '',
		'show_total'     => 'true',
		'post_type'      => 'fellows',
		'columns'        => 3,
		'posts_per_page' => 9,
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
	);
	echo create_gallery_instance( $params );
}

echo '</main>';
require 'modules/newsletter.php';
get_footer();
