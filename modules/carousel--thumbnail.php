<?php $post_type = get_sub_field('thumbnail_carousel_posts'); ?>

<!-- to add later: buttons with carousel js -->
<?php if ( $post_type ): ?>
	<ul>
	<?php foreach ( $post_type as $post ): setup_postdata( $post ); ?>
		<li>
			
		</li>
	<?php endforeach; wp_reset_postdata(); ?>
	</ul>
<?php endif ?>

<!-- 

	foreach loop, $post type variable set outside template
		include thumbnail template (all post types will have a 'thumbnail title' field set in the CMS, with a the_title fallback) -->
