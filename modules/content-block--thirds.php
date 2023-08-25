<section class="wt_content-block--thirds">
<?php if ( $content_block_image ): ?>
	<aside class="wt_content-block--thirds__image" role="img" aria-label="<?php echo $content_block_image['alt']; ?>" style="background-image:url(<?php echo $content_block_image['url']; ?>);"></aside>
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