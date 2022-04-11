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

<section class="wt_grantee__navigation">
	<h1 class="wt_grantee__navigation--title">Other Grantees</h1>
	<h2 class="wt_grantee__navigation--subheader"> Lorem, ipsum dolor sit amet consectetur, adipisicing elit. Quis eius pariatur, praesentium, dicta veritatis illum.</h2>
	<?php // testimonials
		$current_grantee = get_the_ID();
		$grantees = new WP_Query( 
			array(
				'post_type'=>'grantees',
				'post__not_in' => array( $current_grantee ),
				'posts_per_page' => 15
			) 
		);

		if( $grantees->have_posts() ){		
			while( $grantees->have_posts() ){
				$grantees->the_post();

				include( locate_template('modules/grantee-thumbnail.php') );
			}
		}
		wp_reset_postdata();
	?>
</section>