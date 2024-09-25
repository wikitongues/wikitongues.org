<?php

// header
get_header();

// $standard_name = get_field('standard_name');
$videos = get_field('speakers_recorded');
$videos_count = count($videos);
$lexicon_source = get_field('lexicon_source');
$lexicon_target = get_field('lexicon_target');
$lexicons = array_merge($lexicon_source, $lexicon_target);
$lexicons_count = count($lexicons);
$external_resources = get_field('external_resources');
$external_resources_count = count($external_resources);
$nations_of_origin = get_field('nations_of_origin');
// $linguistic_genealogy = get_field('linguistic_genealogy');

// language single banner
include( 'modules/banner--languages-single.php' );

// left column language metada
include( 'modules/meta--languages-single.php' );
echo '<main class="wt_single-languages__content">';
// videos loop (content blocks - grid)
include( 'modules/single-languages__videos.php' );

// dictionaries (content blocks - grid)
include( 'modules/single-languages__lexicons.php' );

// language indexing resources (content blocks - grid)
include( 'modules/single-languages__resources.php' );

echo '</main>';

$custom_title = 'Other languages from '.$nations_of_origin;
$custom_post_type = 'languages';
$custom_class = 'full';
$custom_columns = 4;
$custom_posts_per_page = 8;
$custom_orderby = 'rand';
$custom_order = 'asc';
$custom_pagination = 'false';
$custom_meta_key = 'nations_of_origin';
$custom_meta_value = $nations_of_origin;
$custom_selected_posts = '';
echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');

// other posts (revitalization projects, translation/etc, learning options) - add in later version
// other languages (thumbnail carousel)
// include( 'modules/carousal--thumbnail.php' );

include( 'modules/newsletter.php' );

get_footer();