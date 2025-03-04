<section class="block thirds">
<?php if ( $content_block_image ): ?>
	<div
		class="thumbnail"
		role="img"
		aria-label="<?php echo get_post_meta($content_block_image, '_wp_attachment_image_alt', TRUE); ?>"
		style="background-image:url(<?php echo wp_get_attachment_url($content_block_image) ?>);">
	</div>
<?php elseif ( !$content_block_image && $post->post_type !== 'lexicons' && $post->post_type !== 'resources' ): ?>
	<div class="thumbnail blank" role="img" aria-label="<?php echo $content_block_image['alt']; ?>" style="background-image:url(<?php echo $content_block_image['url']; ?>);"></div>
<?php else: ?>
	<!-- show nothing -->
<?php endif; ?>
	<div class="copy">
		<strong>
			<?php echo $content_block_header; ?>
		</strong>
		<?php if ( $content_block_copy ): ?>
		<p>
			<?php echo $content_block_copy; ?>
		</p>
		<?php endif; ?>
		<a href="<?php echo $content_block_cta_link; ?>" class="<?php echo $content_block_class; ?>">
			<?php echo $content_block_cta_text; ?>
		</a>
	</div>
</section>