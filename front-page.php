<?php 

// header
get_header(); // do pseudo code

// banner
include( get_template('banner.php') );

// thumbnail carousel - revitalization projects
include( get_template('carousel-thumbnail.php') );

// language revitalization
include( get_template('content-block--wide.php') );

// testimonial carousel
include( get_template('carousel-testimonial.php') );

// language archive
include( get_template('content-block--wide.php') );

// thumbnail carousel - archive
include( get_template('thumbnail--carousel.php') );

// footer
get_footer();