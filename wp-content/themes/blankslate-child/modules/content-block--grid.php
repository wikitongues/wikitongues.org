<section class="wt_content-block--thirds">
<?php if ( $content_block_image ): ?>
	<aside
		class="wt_content-block--thirds__image"
		role="img"
		aria-label="<?php echo get_post_meta($content_block_image, '_wp_attachment_image_alt', TRUE); ?>"
		style="background-image:url(<?php echo wp_get_attachment_url($content_block_image) ?>);">
	</aside>
<?php elseif ( !$content_block_image && $post->post_type !== 'lexicons' && $post->post_type !== 'resources' ): ?>
	<aside class="wt_content-block--thirds__image blank" role="img" aria-label="<?php echo $content_block_image['alt']; ?>" style="background-image:url(<?php echo $content_block_image['url']; ?>);"></aside>
<?php else: ?>
	<!-- show nothing -->
<?php endif; ?>
	<aside class="wt_content-block--thirds__copy">
		<h1>
			<?php echo $content_block_header; ?>
		</h1>
		<?php if ( $content_block_copy ): ?>
		<p>
			<?php echo $content_block_copy; ?>
		</p>
		<?php endif; ?>
		<a href="<?php echo $content_block_cta_link; ?>">
			<?php echo $content_block_cta_text; ?>
			<i class="fa-regular fa-arrow-right-long"></i>
		</a>
	</aside>
</section>