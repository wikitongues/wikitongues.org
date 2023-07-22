<?php /* Template name: Revitalization Toolkit */

// header
get_header();

// banner
include( get_template('modules/banner.php') );

// foreach available toolkit download, display 1/3 content blocks
include( get_template('modules/content-block--thirds') );

// footer
get_footer();