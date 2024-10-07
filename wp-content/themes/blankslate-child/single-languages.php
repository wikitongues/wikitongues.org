<?php
$standard_name = get_field('standard_name');
$external_resources = get_field('external_resources');
$external_resources = is_array($external_resources) ? $external_resources : [];
$external_resources_count = count($external_resources);
$nations_of_origin = get_field('nations_of_origin');
// $linguistic_genealogy = get_field('linguistic_genealogy');

// ====================
// Manage Language Page Titles
// ====================
if (is_singular('languages')) {
    if ($standard_name) {
        echo '<script>document.title = "Wikitongues | ' . esc_js($standard_name) . '";</script>';
    }
}

get_header();

include( 'modules/banner--languages-single.php' );
include( 'modules/meta--languages-single.php' );

echo '<main class="wt_single-languages__content">';

include( 'modules/single-languages__fellows.php' );
include( 'modules/single-languages__videos.php' );
include( 'modules/single-languages__lexicons.php' );
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

include( 'modules/newsletter.php' );

get_footer();