<?php /* Template name: Revitalization Fellows */

// header
get_header();

// banner
include( get_template('modules/banner.php');

// foreach fellow post type, include 1/3 content blocks
include( get_template('modules/content-block--thirds') );

// footer
get_footer();