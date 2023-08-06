<?php

// header
get_header();

// video
include( 'modules/videos-single--embed.php' );

// left column - video metadata
include( 'modules/meta--videos-single.php' );

// right column - video content
include( 'modules/main-content--videos-single.php' );

// related videos (thumbnail carousel)
include( 'modules/carousel--thumbnail.php' );

// footer
get_footer();

