<?php /* Template name: Archive Home */

// header
get_header();

// search bar
include( get_template('searchbar--wide.php') );

// search results (one-column thumbnail loop)
include( get_template('search-results.php') );

// content blocks - thirds (add in later version, how to filter?)

// footer
get_footer();