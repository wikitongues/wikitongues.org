<?php /* Template name: Cohort */

// header
get_header();

// cohort banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

// testimonials
$grantees = new WP_Query( 
	array(
		'post_type'=>'grantees',
		'meta_key' => 'grantee_role',
		'meta_value' => 'Project leader',
		'posts_per_page' => 15
	) 
);

echo '<main class="wt_wrapper">';

if( $grantees->have_posts() ){
	echo '<h1>Meet the 2022 Cohort</h1>';
	
	while( $grantees->have_posts() ){
		$grantees->the_post();

		include( locate_template('modules/grantee-thumbnail.php') );
	}
}
wp_reset_postdata();

echo '</main>';

// footer
get_footer();