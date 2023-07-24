<?php

// header
get_header();

// language single banner
include( get_template('modules/banner--language-single.php') );

// left column language metada
include( get_template('modules/language-single--metada.php') );

// videos loop (content blocks - thirds)
include( get_template('modules/content-block--thirds.php') );

// dictionaries (content blocks - thirds)
include( get_template('modules/content-block--thirds.php') );

// language indexing resources (content blocks - thirds)
include( get_template('modules/content-block--thirds.php') );

// other posts (revitalization projects, translation/etc, learning options) - add in later version

// other languages (thumbnail carousel)
include( get_template('modules/carousal--thumbnail.php') );

// footer
get_footer();