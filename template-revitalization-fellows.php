<?php /* Template name: Revitalization Fellows */

// header
get_header();

// banner
include('modules/banner.php');

// foreach fellow post type, include 1/3 content blocks
include('modules/content-block--thirds');

// footer
get_footer();