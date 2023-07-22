<?php /* Template name: Revitalization Toolkit */

// header
get_header();

// banner
include( get_template('banner.php') );

// foreach available toolkit download, display 1/3 content blocks
include( get_template('content-block--thirds') );

// content block - wide (fellowship/accelerator prompt)

// footer
get_footer();