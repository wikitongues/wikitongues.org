<?php

/* Template name: Giving Campaign */

// header
get_header();

// banner
$page_banner = get_field( 'giving_campaign_banner' );

require 'modules/banners/banner--main.php';

// giving modules and content loop
$progress_bar  = get_field( 'progress_bar' );
$donation_link = get_field( 'donation_link' );

require 'modules/main-content--giving-campaign.php';

require 'modules/newsletter.php';

get_footer();
