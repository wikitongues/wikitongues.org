<?php

// header
get_header();

// reset searchbar
include( 'modules/banner--searchbar.php' );

// pull search results loop
include( 'modules/search-results.php' );

include( 'modules/newsletter.php' );

get_footer();

?>