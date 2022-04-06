<article class="wt_grantee__thumbnail">
	<a href="<?php the_permalink(); ?>">
		<img class="wt_grantee__thumbnail--image" src="<?php echo get_the_post_thumbnail_url(); ?>" alt="">
		<div class="wt_grantee__thumbnail--text">
			<h2><?php the_title(); ?></h2>
			<p>
				<span>The </span>
				<span>
					<?php the_field('grantee_language'); ?>
				</span>
				<span> language</span>
			</p>
			<p><?php the_field('grantee_location'); ?></p>
		</div>
	</a>
</article>