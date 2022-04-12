<article class="wt_grantee__collaborator">
	<?php $grantee_headshot = get_the_post_thumbnail_url(); ?>
	<section class="wt_grantee__collaborator--image<?php if(!$grantee_headshot):?> empty<?php endif; ?>"
	   style="background-image:url(<?php echo get_the_post_thumbnail_url(); ?>);"
	   role="img"
	   aria-label="<?php the_title(); ?>">
	</section>
	<section class="wt_grantee__collaborator--text">
		<?php the_field('grantee_bio'); ?>
	</section>
	<div class="clear"></div>
</article>