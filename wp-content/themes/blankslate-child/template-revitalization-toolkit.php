<?php /* Template name: Revitalization Toolkit */

// header
get_header();

// banner
$page_banner = get_field('revitalization_toolkit_banner');

include( 'modules/banner--main.php' );

// foreach linked page, display 1/3 content block
if ( have_rows( 'content_blocks' ) ) {

	echo '<main class="wt_content-block--thirds__wrapper">';

	while ( have_rows( 'content_blocks' ) ) {

		the_row();

		$content_block_image = get_sub_field('content_block_image');
		$content_block_header = get_sub_field('content_block_header');
		$content_block_copy = get_sub_field('content_block_copy');
		$content_block_cta_link = get_sub_field('content_block_cta');
		$content_block_class = get_sub_field('content_block_class');
		$content_block_cta_text = 'Download';

		include( 'modules/content-block--grid.php' );
	}

	echo '</main>';
}

include( 'modules/newsletter.php' );

get_footer();