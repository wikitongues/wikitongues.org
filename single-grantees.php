<?php

// header
get_header();

// splash banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

// the_field('grantee_language');
// the_field('grantee_location');
 ?>

<main class="wt_grantee__main">
	<h1 class="wt_grantee__main--title">
		<?php the_title(); ?>
	</h1>
	<h2 class="wt_grantee__main--subheader">
		<?php the_field('grantee_pitch'); ?>
	</h2>
	<article class="wt_grantee__main--content">
		<?php the_field('grantee_description'); ?>
	</article>
</main> 