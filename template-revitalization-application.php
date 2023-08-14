<?php

/* Template name: Revitalization Application */

// header
get_header();

// banner
$page_banner = get_field('revitalization_application_banner');

include( 'modules/banner.php' );

// if applications are open, display button
// include( 'modules/button--wide.php' );

// standard content loop - do we need a var outside the template?
include( 'modules/main-content.php' );

// footer
get_footer();