<?php /* Template name: Donors */

// header
get_header();

// banner

// paginated list of all donors
include( locate_template('modules/donors.php') );

// paginated list of monthly donors
include( locate_template('modules/donors.php') );

// information for DAFs
include( locate_template('modules/dafs.php') );

// donate form
include( locate_template('modules/donate.php') );

// newsletter signup
include( locate_template('modules/newsletter.php') );

// footer
get_footer();