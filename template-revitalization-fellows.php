<?php /* Template name: Revitalization Fellows */

// header
get_header();

// banner
$page_banner = get_field('revitalization_fellows_banner');

include( 'modules/banner--main.php' );

// foreach linked page, display 1/3 content block
$query = new WP_Query( array( 'post_type' => 'fellows' ) );

if ( $query->have_posts() ) {

	echo '<main class="wt_content-block--thirds__wrapper">';

	while ( $query->have_posts() ) {

		$query->the_post();

		$first_name = get_field('first_name');
		$last_name = get_field('last_name');
		$fellow_language = get_field('fellow_language');
		$fellow_location = get_field('fellow_location');
		$content_block_image = get_field('fellow_image');
		$content_block_header = $first_name . ' ' . $last_name;
		$content_block_copy = '<strong>' . $fellow_language . '</strong><br /><span>' . $fellow_location . '</span>';
		$content_block_cta_link = get_the_permalink();
		$content_block_cta_text = 'Read more';

		include( 'modules/content-block--grid.php' );
	}

	echo '</main>';

	wp_reset_postdata();
}

// footer
get_footer();