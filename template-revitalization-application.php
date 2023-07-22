<?php

/* Template name: Revitalization Application */

// header
get_header();

// banner
include( get_template('modules/banner.php') );

// if applications are open, display button
include( get_template('modules/button--wide.php') );

// standard content loop - do we need a var outside the template?
include( get_template('modules/main-content.php') );

// footer
get_footer();