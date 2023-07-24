<?php

// header
get_header();

// banner
include( get_template('modules/banner--fellows-single.php') );

// content
include( get_template('modules/fellows-single--metadata') );
include ( get_template('modules/main-content.php' );

// loop through other fellows
include( get_template('modules/carousal--thumbnail.php') );

get_footer();