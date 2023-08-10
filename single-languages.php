<?php

// header
get_header();

$videos = get_field('speakers_recorded');
$lexicon_source = get_field('lexicon_source');
$lexicon_target = get_field('lexicon_target');
$lexicons = array_merge($lexicon_source, $lexicon_target);

// language single banner
include( 'modules/banner--language-single.php' );

// left column language metada
include( 'modules/meta--languages-single.php' );

// videos loop (content blocks - thirds)
include( 'modules/languages-single__videos.php' );

// dictionaries (content blocks - thirds)
include( 'modules/content-block--thirds.php' );

// language indexing resources (content blocks - thirds)
// include( 'modules/content-block--thirds.php' );

// other posts (revitalization projects, translation/etc, learning options) - add in later version

// other languages (thumbnail carousel)
// include( 'modules/carousal--thumbnail.php' );

// footer
get_footer();