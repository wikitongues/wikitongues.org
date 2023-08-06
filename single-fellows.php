<?php

// header
get_header();

// banner
include( 'modules/banner--fellows-single.php' );

// content - consider consolidating?
include( 'modules/meta--fellows-single' );
include( 'modules/main-content.php' );

// loop through other fellows
include( 'modules/carousal--thumbnail.php' );

get_footer();