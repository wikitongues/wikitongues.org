<?php $post_type = get_sub_field('testimonial_source'); ?>

<!-- to add later: buttons with carousel js -->
<?php if ( $post_type ): ?>
	<section class="wt_carousel--testimonials">	
	<p>
		<strong>What our community is saying</strong>
	</p>
	<ul>
		<?php foreach ( $post_type as $post ): setup_postdata( $post ); ?>
		<?php 
			$testimonial_image = get_field('fellow_headshot');
			$testimonial_copy = get_field('fellow_testimonial');
			$first_name = get_field('first_name');
			$last_name = get_field('last_name');
			$testimonial_name = $first_name . " " . $last_name;
			$testimonial_location = get_field('fellow_location');
			$testimonial_link_back = get_field('testimonial_link_back');
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
					<?php if ( $testimonial_link_back ): ?>
					<a href="<?php echo get_the_permalink(); ?>">
						<span>Read more</span>
						<i class="fa-regular fa-arrow-right-long"></i>
					</a>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; wp_reset_postdata(); ?>
		</ul>
	</section>
<?php endif ?>