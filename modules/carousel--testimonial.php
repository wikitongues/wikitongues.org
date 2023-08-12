<?php $post_type = get_sub_field('testimonial_source'); ?>

<!-- to add later: buttons with carousel js -->
<?php if ( $post_type ): ?>
	<ul class="wt_carousel--testimonials">
	<?php foreach ( $post_type as $post ): setup_postdata( $post ); ?>
	<?php 
		$testimonial_image = get_field('fellow_image');
		$testimonial_copy = get_field('fellow_testimonial');
		$first_name = get_field('first_name');
		$last_name = get_field('last_name');
		$testimonial_name = $first_name . " " . $last_name;
		$testimonial_location = get_field('testimonial_location');
		?>
		<li class="wt_testimonial">
			<img class="wt_testimonial__image" src="<?php echo $testimonial_image['url']; ?>" alt="<?php echo $testimonial_image['alt']; ?>">

			<div class="wt_testimonial__copy">
				<p>
					<span><?php echo $testimonial_copy; ?></span>
					<span>
						<em>&#8212 <?php echo $testimonial_name . ", " . $testimonial_location; ?></em>
					</span>
				</p>
				<a href="<?php echo get_the_permalink(); ?>">Read more</a>
			</div>
		</li>
	<?php endforeach; wp_reset_postdata(); ?>
	</ul>
<?php endif ?>