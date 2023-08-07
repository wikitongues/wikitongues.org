<?php $post_type = get_sub_field('thumbnail_carousel_posts'); ?>

<!-- to add later: buttons with carousel js -->
<?php if ( $post_type ): ?>
	<ul class="wt_carousel--thumbnails">
	<?php foreach ( $post_type as $post ): setup_postdata( $post ); ?>
	<?php 
		$thumbnail_title = get_field('thumbnail_title');
		$thumbnail_cta_text = get_field('thumbnail_cta_text'); 
		$thumbnail_image = get_field('thumbnail_image');
		?>
		<li class="wt_thumbnail">
		<?php 
			if ( $thumbnail_image ) {

				echo '<img class="wt_thumbnail__image" src="' . 
					 $thumbnail_image['url'] .
					 '" alt="' .
					 $thumbnail_image['alt'] .
					 '">';

			} else {

				the_post_thumbnail( 'large' );

			} ?>

			<div class="wt_thumbnail__copy">
			<?php

				if ( $thumbnail_title ) {

					echo '<p>' . $thumbnail_title . '</p>';

				} else {

					echo '<p>' . the_title() . '</p>';

				}

				if ( $thumbnail_cta_text ) {

					echo '<a href="' . get_the_permalink() . '">' .
						 $thumbnail_cta_text . 
						 '</a>'; // add arrow

				} else {

					echo '<a href="' . get_the_permalink() . '">' .
						 'Read more</a>'; // add arrow

				}

			?>
			</div>
		</li>
	<?php endforeach; wp_reset_postdata(); ?>
	</ul>
<?php endif ?>

<!-- 
	foreach loop, $post type variable set outside template
		include thumbnail template (all post types will have a 'thumbnail title' field set in the CMS, with a the_title fallback) -->
