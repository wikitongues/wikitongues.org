<?php

// header
get_header();

$videos = get_field('speakers_recorded');
$videos_count = count($videos);
$lexicon_source = get_field('lexicon_source');
$lexicon_target = get_field('lexicon_target');
$lexicons = array_merge($lexicon_source, $lexicon_target);
$lexicons_count = count($lexicons);

// language single banner
include( 'modules/banner--languages-single.php' );

// left column language metada
include( 'modules/meta--languages-single.php' );

// videos loop (content blocks - grid)
include( 'modules/single-languages__videos.php' );

// dictionaries (content blocks - grid)
include( 'modules/single-languages__lexicons.php' );

// language indexing resources (content blocks - grid)
include( 'modules/content-block--grid.php' );

// other posts (revitalization projects, translation/etc, learning options) - add in later version

// other languages (thumbnail carousel)
// include( 'modules/carousal--thumbnail.php' );

// footer
get_footer();