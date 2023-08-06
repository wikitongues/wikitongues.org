<?php

// header
get_header();

// language single banner
include( 'modules/banner--language-single.php' );

// left column language metada
include( 'modules/meta--languages-single.php' );

// videos loop (content blocks - thirds)
include( 'modules/content-block--thirds.php' );

// dictionaries (content blocks - thirds)
include( 'modules/content-block--thirds.php' );

// language indexing resources (content blocks - thirds)
include( 'modules/content-block--thirds.php' );

// other posts (revitalization projects, translation/etc, learning options) - add in later version

// other languages (thumbnail carousel)
include( 'modules/carousal--thumbnail.php' );

// footer
get_footer();