<section class="wt_content-block--thirds">
	<aside class="wt_content-block--thirds__image" role="img" aria-label="<?php echo $content_block_image['alt']; ?>" style="background-image:url(<?php echo $content_block_image['url']; ?>);"></aside>
	<aside class="wt_content-block--thirds__copy">
		<h1>
			<?php echo $content_block_header; ?>
		</h1>
		<?php if ( $content_block_copy ): ?>
		<p>
			<?php echo $content_block_copy; ?>
		</p>
		<?php endif; ?>
		<a href="<?php echo $content_block_cta['url']; ?>">
			<?php echo $content_block_cta['title']; ?>
		</a>
	</aside>
</section>