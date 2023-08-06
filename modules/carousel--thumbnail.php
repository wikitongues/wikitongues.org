<!-- to add later: buttons with carousel js -->
<?php if ( $post_type ): ?>
	<ul>
	<?php foreach ( $thumbnail_carousel_posts as $post ): setup_postdata( $post ); ?>
		<li>
			<h1>post</h1>
		</li>
	<?php endforeach; wp_reset_postdata(); ?>
	</ul>
<?php endif ?>

<!-- 

	foreach loop, $post type variable set outside template
		include thumbnail template (all post types will have a 'thumbnail title' field set in the CMS, with a the_title fallback) -->
