<?php 
	$post_type = get_sub_field('thumbnail_carousel_posts'); 
	$carousel_title = get_sub_field('thumbnail_carousel_title');
?>
<?php if ( $post_type ): ?>
<section class="wt_carousel__wrapper"><!-- fix naming conventions -->
	<?php echo '<h1>' . $carousel_title . '</h1>'; ?>
	<button class="wt_carousel__left-scroll">
		<i class="fa-regular fa-arrow-left-long"></i>
	</button>
	<button class="wt_carousel__right-scroll">
		<i class="fa-regular fa-arrow-right-long"></i>
	</button>
	<ul class="wt_carousel--thumbnails">
	<?php foreach ( $post_type as $post ): setup_postdata( $post ); ?>
	<?php 
		$custom_title = get_field('thumbnail_title');
		$video_title = get_field('video_title');
		$thumbnail_cta_text = get_field('thumbnail_cta_text'); 
		$custom_thumbnail = get_field('thumbnail_image')['url'];
		$video_thumbnail = wp_get_attachment_url(get_field('video_thumbnail_v2'));
		$thumbnail_title = is_null($custom_title) ? $video_title : $custom_title;
		$thumbnail_image = is_null($custom_thumbnail) ? $video_thumbnail : $custom_thumbnail;
		?>
		<li class="wt_thumbnail">
		<?php 
			if ( $thumbnail_image ) {
				echo '<div class="wt_thumbnail__image" role="img"' . 'style="background-image:url(' .  $thumbnail_image . ');" alt="' . $thumbnail_image['alt'] . '"></div>';
			} elseif ( has_post_thumbnail() ) {
				// the_post_thumbnail( 'large', array('class'=>'wt_thumbnail__image') );
				echo '<div class="wt_thumbnail__image" role="img"' . 'style="background-image:url(' . get_the_post_thumbnail_url() . ');" alt="' . $thumbnail_image['alt'] . '"></div>';
			} else {

				echo '<div class="wt_thumbnail__image blank" role="img"></div>';

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
						 '<i class="fa-regular fa-arrow-right-long"></i></a>'; // add arrow

				} else {

					echo '<a href="' . get_the_permalink() . '">' .
						 'Read more<i class="fa-regular fa-arrow-right-long"></i></a>'; // add arrow

				}

			?>
			</div>
		</li>
	<?php endforeach; wp_reset_postdata(); ?>
	</ul>
</section>
<?php endif ?>

<!-- 
	foreach loop, $post type variable set outside template
		include thumbnail template (all post types will have a 'thumbnail title' field set in the CMS, with a the_title fallback) -->
