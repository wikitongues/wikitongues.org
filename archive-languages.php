<?php 

// header
get_header();

// banner with metadata
$banner_image = get_field('banner_image', 'options');
$banner_header = get_field('banner_header', 'options');
$banner_copy = get_field('banner_copy', 'options');

include( locate_template('modules/banner.php') );

// metrics
include( locate_template('modules/metrics-options.php') );

// search/contribute calls to action
include( locate_template('modules/languages-actions.php') );

// search window popup/overlay
include( locate_template('modules/languages-search.php') );

get_footer();