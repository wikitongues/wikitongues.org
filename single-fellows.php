<?php

// header
get_header();

// banner
include( get_template('modules/banner--fellows-single.php') );

// content - consider consolidating?
include( get_template('modules/meta--fellows-single') );
include( get_template('modules/main-content.php' );

// loop through other fellows
include( get_template('modules/carousal--thumbnail.php') );

get_footer();