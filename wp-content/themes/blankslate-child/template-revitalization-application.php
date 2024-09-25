<?php

/* Template name: Revitalization Application */

// header
get_header();

// banner
$page_banner = get_field('revitalization_application_banner');

// bug - banner displays twice, hiding with css
include( 'modules/banner--main.php' );

// if applications are open, display button
// include( 'modules/button--wide.php' );

// standard content loop - do we need a var outside the template?
include( 'modules/main-content.php' );

include( 'modules/newsletter.php' );

get_footer();