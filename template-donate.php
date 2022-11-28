<?php /* Template name: Donate */

// header
get_header();

// vars
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');
$donate_page_header = get_field('donate_page_header');
$donate_subheader = get_field('donate_subheader');
$donate_form_embed = get_field('donate_form_embed');
$donate_address = get_field('donate_address');
$donate_content = get_field('donate_content');
$case_studies = get_field('case_studies');

// banner
include( locate_template('modules/banner.php') );

// main content
include( locate_template('modules/donate-content.php') );

// donate module

get_footer();