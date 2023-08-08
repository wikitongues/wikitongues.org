<?php /* Template name: Revitalization Home */

// header
get_header();

// banner
$page_banner = get_field('revitalization_home_banner');

include( 'modules/banner.php' );

// foreach linked page, display 1/3 content block
include( 'modules/content-block--thirds' );

// footer
get_footer();