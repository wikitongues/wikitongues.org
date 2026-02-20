<?php
	$territory_id = get_the_ID();

	get_header();
	$territory = wt_prefix_the( get_the_title() );

	echo '<div class="wt_meta--territories-single">';
	require 'modules/territories/meta--territories-single.php';
	echo '</div>';


	echo '<main class="wt_single-territories__content">';
	// Pass false to avoid hydrating full WP_Post objects — we only need IDs.
	// Territories like India (403 languages) would otherwise time out.
	$languages    = get_field( 'languages', get_the_ID(), false );
	$language_ids = $languages ? implode( ',', $languages ) : '';
	// Gallery
	$title  = 'Languages of ' . $territory;
	$params = array(
		'title'          => $title,
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'languages',
		'custom_class'   => '',
		'columns'        => 4,
		'posts_per_page' => 20,
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
