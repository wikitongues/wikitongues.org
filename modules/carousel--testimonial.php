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
			<img class="wt_testimoinal__image" src="<?php $thumbnail_image['url']; ?>" alt="<?php $thumbnail_image['alt']; ?>">

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

<!-- 
	foreach loop, $post type variable set outside template
		include thumbnail template (all post types will have a 'thumbnail title' field set in the CMS, with a the_title fallback) -->
<!-- testimonial "read more" for later version" -->
<!-- attach testimonial content to fellow post object -->