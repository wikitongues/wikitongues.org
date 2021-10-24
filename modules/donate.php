<?php
	$donate_header = get_field('donate_header');
	$testimonial_photo = get_field('testimonial_photo');
	$testimonial_copy = get_field('testimonial_copy');
	$testimonial_byline = get_field('testimonial_byline');
	$donate_call = get_field('donate_call');
	$donate_form_embed = get_field('donate_form_embed'); ?>

<div class="wt_donate">
	<h1>
		<?php echo $donate_header; ?>
	</h1>

	<?php if ( $testimonial_photo && 
			   $testimonial_copy  && 
			   $testimonial_byline ) {
			include( locate_template('modules/testimonials.php') );
		} ?>

	<aside class="wt_donate__call">
		<h1>
			<?php echo $donate_call; ?>
		</h1>
	</aside>
	<aside>
		<?php echo $donate_form_embed; ?>
	</aside>
	<div class="clear"></div>
</div>