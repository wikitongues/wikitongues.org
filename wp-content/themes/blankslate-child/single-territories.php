<?php
	$territory_id   = get_the_ID();
	$territory_slug = get_post_field( 'post_name', $territory_id );

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
		$fellows_params = wt_gallery_params(
			array(
				'title'          => 'Fellows from ' . $territory,
				'post_type'      => 'fellows',
				'columns'        => 3,
				'posts_per_page' => 3,
				'selected_posts' => implode( ',', $fellow_ids ),
				'link_out'       => add_query_arg( 'territory', $territory_slug, get_post_type_archive_link( 'fellows' ) ),
			)
		);
		echo create_gallery_instance( $fellows_params );
	}

	// Languages gallery.
	// Pass false to avoid hydrating full WP_Post objects — we only need IDs.
	// Territories like India (403 languages) would otherwise time out.
	$languages    = get_field( 'languages', get_the_ID(), false );
	$language_ids = $languages ? implode( ',', $languages ) : '';
	$params       = wt_gallery_params(
		array(
			'title'          => 'Languages of ' . $territory,
			'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
			'post_type'      => 'languages',
			'columns'        => 3,
			'posts_per_page' => 6,
			'orderby'        => $language_ids,
			'selected_posts' => $language_ids,
			'display_blank'  => 'true',
			'link_out'       => add_query_arg( 'territory', $territory_slug, get_post_type_archive_link( 'languages' ) ),
		)
	);
	echo create_gallery_instance( $params );

	echo '</main>';

	require 'modules/newsletter.php';
	get_footer();
