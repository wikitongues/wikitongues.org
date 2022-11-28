<?php

if ( !is_archive() /*&& !is_page_template('template-donate.php')*/ ){
	$donate_header = get_field('donate_header');
	$testimonial_photo = get_field('testimonial_photo');
	$testimonial_copy = get_field('testimonial_copy');
	$testimonial_byline = get_field('testimonial_byline');
	$donate_call = get_field('donate_call');
	$donate_link = get_field('donate_link'); 
	$p2p_link = get_field('p2p_link');
} else {
	$donate_header = get_field('donate_header','options');
	$testimonial_photo = get_field('testimonial_photo','options');
	$testimonial_copy = get_field('testimonial_copy','options');
	$testimonial_byline = get_field('testimonial_byline','options');
	$donate_call = get_field('donate_call','options');
	$donate_link = get_field('donate_link','options'); 
	$p2p_link = get_field('p2p_link','options');	
} ?>

<div class="wt_donate">
	<h1>
		<?php echo $donate_header; ?>
	</h1>

	<h2 class="wt_donate__call">
		<?php echo $donate_call; ?>
	</h2>

	<?php if ( $testimonial_photo && 
			   $testimonial_copy  && 
			   $testimonial_byline ) {
			include( locate_template('modules/testimonials.php') );
		} ?>

	<?php if ( $donate_link ): ?>
	<a href="<?php echo $donate_link; ?>"
	   class="wt_action__primary">
	   	Donate
	</a>
	<?php endif; ?>
	
	<?php if ( $p2p_link ): ?>
	<a href="<?php echo $p2p_link; ?>"
	   class="wt_action__secondary">
		<span><i class="fad fa-arrow-circle-right"></i></span>
		<span>Or start a fundraiser</span>
	</a>
	<?php endif; ?> 
</div>