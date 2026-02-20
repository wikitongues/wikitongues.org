<?php
	$territory_id = get_the_ID();

	get_header();
	$territory = wt_prefix_the( get_the_title() );

	echo '<div class="wt_meta--territories-single">';
	require 'modules/territories/meta--territories-single.php';
	echo '</div>';

	echo '<main class="wt_single-territories__content">';

	// Fellows gallery — shown first if any fellows are linked to this territory.
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
		$fellows_params = array(
			'title'          => 'Fellows from ' . $territory,
			'subtitle'       => '',
			'show_total'     => 'true',
			'post_type'      => 'fellows',
			'custom_class'   => '',
			'columns'        => 3,
			'posts_per_page' => 6,
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
		echo create_gallery_instance( $fellows_params );
	}

	// Languages gallery.
	// Pass false to avoid hydrating full WP_Post objects — we only need IDs.
	// Territories like India (403 languages) would otherwise time out.
	$languages    = get_field( 'languages', get_the_ID(), false );
	$language_ids = $languages ? implode( ',', $languages ) : '';
	$params       = array(
		'title'          => 'Languages of ' . $territory,
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'languages',
		'custom_class'   => '',
		'columns'        => 3,
		'posts_per_page' => 6,
		'orderby'        => $language_ids,
		'order'          => 'asc',
		'pagination'     => 'true',
		'meta_key'       => '',
		'meta_value'     => '',
		'selected_posts' => $language_ids,
		'display_blank'  => 'true',
		'exclude_self'   => 'false',
		'taxonomy'       => '',
		'term'           => '',
	);
	echo create_gallery_instance( $params );

	echo '</main>';

	require 'modules/newsletter.php';
	get_footer();
