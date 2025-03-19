<?php /* Template name: Revitalization Home */

// header
get_header();
// include( 'modules/editorial-content.php' );
$migration = get_field('migration_assistant');

if ($migration === true) {
	include( 'modules/editorial-content.php' );
} else {
	// banner
	$page_banner = get_field('revitalization_home_banner');

	include( 'modules/banner--main.php' );

	// foreach linked page, display 1/3 content block
	if ( have_rows( 'content_blocks' ) ) {

		echo '<main class="wrapper thirds">';

		while ( have_rows( 'content_blocks' ) ) {

			the_row();

			$content_block_image = get_sub_field('content_block_image');
			$content_block_header = get_sub_field('content_block_header');
			$content_block_copy = get_sub_field('content_block_copy');
			$content_block_cta = get_sub_field('content_block_cta');
			$content_block_cta_link = $content_block_cta['url'];
			$content_block_cta_text = $content_block_cta['title'];

			include( 'modules/content-block--grid.php' );
		}

		echo '</main>';
	}
}
include( 'modules/newsletter.php' );

get_footer();