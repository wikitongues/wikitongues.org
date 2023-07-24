<?php

// header
get_header();

// video
include( get_template('modules/videos-single--embed.php') );

// left column - video metadata
include( get_template('modules/meta--videos-single.php') );

// right column - video content
include( get_template('modules/main-content--videos-single.php') );

// related videos (thumbnail carousel)
include( get_template('modules/carousel--thumbnail.php') );

// footer
get_footer();

