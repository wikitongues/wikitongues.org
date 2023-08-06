<?php /* Template name: Revitalization Toolkit */

// header
get_header();

// banner
include( 'modules/banner.php' );

// foreach available toolkit download, display 1/3 content blocks
include( 'modules/content-block--thirds' );

// footer
get_footer();