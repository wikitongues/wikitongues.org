<?php
	$territory_id = get_the_ID();

	get_header();
	$territory = get_the_title();

	echo '<div class="wt_meta--territories-single">';
	include( 'modules/territories/meta--territories-single.php' );
	echo '</div>';


	echo '<main class="wt_single-territories__content">';
	$languages = get_field('languages');
	$language_ids = [];
	if ($languages) {
		$language_ids = implode(',', wp_list_pluck($languages, 'ID'));
	}
	// Gallery
	$title = 'Languages of '. $territory;
	$params = [
		'title' => $title,
		'subtitle' => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total' => 'true',
		'post_type' => 'languages',
		'custom_class' => '',
		'columns' => 4,
		'posts_per_page' => 20,
		'orderby' => $language_ids,
		'order' => 'asc',
		'pagination' => 'true',
		'meta_key' => '',
		'meta_value' => '',
		'selected_posts' => $language_ids,
		'display_blank' => 'true',
		'exclude_self' => 'false',
		'taxonomy' => '',
		'term' => ''
	];
	echo create_gallery_instance($params);
	echo '</main>';

include( 'modules/newsletter.php' );
get_footer();