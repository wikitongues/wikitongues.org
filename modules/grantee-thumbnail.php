<article class="wt_grantee__thumbnail">
	<a href="<?php the_permalink(); ?>">
		<?php $grantee_headshot = get_the_post_thumbnail_url(); ?>
		<section class="wt_grantee__thumbnail--image<?php if(!$grantee_headshot):?> empty<?php endif; ?>"
		   style="background-image:url(<?php echo get_the_post_thumbnail_url(); ?>);"
		   role="img"
		   aria-label="<?php the_title(); ?>">
		</section>
		<section class="wt_grantee__thumbnail--text">
			<h2><?php the_title(); ?></h2>
			<p>
				<?php $grantee_language = get_field('grantee_language'); ?>
				<span>
					<?php echo $grantee_language; ?>
				</span>
				<?php if( strpos($grantee_language,' and ') ): ?>
					<span>languages</span>
				<?php else: ?>
					<span> language</span>
				<?php endif; ?>
			</p>
			<p><?php the_field('grantee_location'); ?></p>
		</section>
	</a>
</article>