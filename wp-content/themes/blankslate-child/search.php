<?php

// header
get_header();

// reset searchbar
require 'modules/banners/banner--searchbar.php';

// pull search results loop
require 'modules/search/search-results.php';

require 'modules/newsletter.php';

get_footer();
