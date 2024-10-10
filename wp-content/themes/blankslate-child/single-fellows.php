<?php
$first_name = get_field('first_name');
$last_name = get_field('last_name');
$fellow_name = $first_name . ' ' . $last_name;
$page_banner = get_field('fellow_banner');
$fellow_year = get_field('fellow_year');
$fellow_language = get_field('fellow_language');
$fellow_location = get_field('fellow_location');
$fellow_language_preferred_name = get_field('fellow_language_preferred_name');
$categories = get_the_terms(get_the_ID(), 'fellow-category');
$category_names = implode(', ', array_map('esc_html', wp_list_pluck($categories, 'name')));
$social_links = [
	'email'     => ['url' => get_field('email'), 'icon' => 'fa-brands fa-square-email'],
	'facebook'  => ['url' => get_field('facebook'), 'icon' => 'fa-brands fa-square-facebook'],
	'instagram' => ['url' => get_field('instagram'), 'icon' => 'fa-brands fa-instagram'],
	'linkedin'  => ['url' => get_field('linkedin'), 'icon' => 'fa-brands fa-linkedin'],
	'tiktok'    => ['url' => get_field('tiktok'), 'icon' => 'fa-brands fa-tiktok'],
	'twitter'   => ['url' => get_field('twitter'), 'icon' => 'fa-brands fa-twitter'],
	'website'   => ['url' => get_field('website'), 'icon' => 'fa-regular fa-link'],
	'youtube'   => ['url' => get_field('youtube'), 'icon' => 'fa-brands fa-youtube']
];
$revitalization_fellows_url = home_url('/revitalization/fellows/?fellow_year=');
$revitalization_fellows_url = add_query_arg('fellow_year', $fellow_year, $revitalization_fellows_url);

// ====================
// Manage Fellows Page Titles
// ====================
if (is_singular('fellows')) {
	echo '<script>document.title = "Fellows | '. $fellow_name . '";</script>';
}

get_header();

// include( 'modules/banner--main.php' );

include( 'modules/meta--fellows-single.php' );

// fellow narrative/content
include( 'modules/main-content.php' );

$fellow_bio = get_field('fellow_bio');

if ( $fellow_bio ) {
	include( 'modules/fellow-bio.php');
}
?>
<div class="custom-gallery full">
	<h2 class="wt_sectionHeader"><?php echo 'Other fellows from <a href="'.esc_url($revitalization_fellows_url).'">' . $fellow_year ?></a></h2>
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

include( 'modules/newsletter.php' );

get_footer();