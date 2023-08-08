<?php
$content_block_image = get_sub_field('content_block_image');
$content_block_header = get_sub_field('content_block_header');
$content_block_copy = get_sub_field('content_block_copy');
$content_block_cta = get_sub_field('content_block_cta'); 
?>

<section class="wt_content-block--thirds">
	<aside class="wt_content-block--thirds__image" role="img" aria-label="<?php echo $content_block_image['alt']; ?>" style="background-image:url(<?php echo $content_block_image['url']; ?>);"></aside>
	<aside class="wt_content-block--thirds__copy">
		<h1>
			<?php echo $content_block_header; ?>
		</h1>
		<p>
			<?php echo $content_block_copy; ?>
		</p>
		<!-- consolidate field type -->
		<a href="<?php echo $content_block_cta['content_block_cta_link']; ?>">
			<?php echo $content_block_cta['content_block_cta_text']; ?>
		</a>
	</aside>
</section>