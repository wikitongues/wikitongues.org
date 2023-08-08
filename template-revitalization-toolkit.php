<?php /* Template name: Revitalization Toolkit */

// header
get_header();

// banner
$page_banner = get_field('revitalization_toolkit_banner');

include( 'modules/banner.php' );

// foreach linked page, display 1/3 content block
if ( have_rows( 'content_blocks' ) ) {

	while ( have_rows( 'content_blocks' ) ) {

		the_row();
		
		include( 'modules/content-block--thirds' );
	}
}

// footer
get_footer();