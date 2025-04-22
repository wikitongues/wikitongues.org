<?php

// header
get_header();

// reset searchbar
include( 'modules/banners/banner--searchbar.php' );

// pull search results loop
include( 'modules/search/search-results.php' );

include( 'modules/newsletter.php' );

get_footer();

?>