<?php

// header
get_header();

// banner
$page_banner = get_field('fellow_banner');

include( 'modules/banner--main.php' );

// fellow meta
$first_name = get_field('first_name');
$last_name = get_field('last_name');
$fellow_name = $first_name . ' ' . $last_name;
$fellow_year = get_field('fellow_year');
$fellow_language = get_field('fellow_language');
$fellow_language_preferred_name = get_field('fellow_language_preferred_name');
$website = get_field('website');
$email = get_field('email');
$twitter = get_field('twitter');
$instagram = get_field('instagram');
$facebook = get_field('facebook');
$linkedin = get_field('linkedin');
$youtube = get_field('youtube');
$tiktok = get_field('tiktok');

include( 'modules/meta--fellows-single.php' );

// fellow narrative/content
include( 'modules/main-content.php' );

// fellow bio
$fellow_bio = get_field('fellow_bio');

if ( $fellow_bio ) {

	include( 'modules/fellow-bio.php');

}
?>
<div class="custom-gallery full">
	<h2 class="wt_sectionHeader"><?php echo 'Other fellows from ' . $fellow_year ?></h2>
	<p>The Wikitongues Fellowship is an accelerator program where activists can learn from a network of revitalization projects. <a href="">Support a revitalization project.</a></p>
</div>
<?php
$custom_title = '';
$custom_post_type = 'fellows';
$custom_class = 'full';
$custom_columns = 4;
$custom_posts_per_page = 4;
$custom_orderby = 'rand';
$custom_order = '';
$custom_pagination = 'false';
$custom_meta_key = 'fellow_year';
$custom_meta_value = $fellow_year;
$custom_selected_posts = '';
echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');

get_footer();