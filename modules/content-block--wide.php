<?php
$content_block_image = get_sub_field('content_block_image');
$content_block_header = get_sub_field('content_block_header');
$content_block_copy = get_sub_field('content_block_copy');
$content_block_cta = get_sub_field('content_block_cta'); 
?>

<section class="wt_content-block--wide">
	<aside class="wt_content-block--wide__image" role="img" aria-label="<?php echo $content_block_image['alt']; ?>" style="background-image:url(<?php echo $content_block_image['url']; ?>);"></aside>
	<aside class="wt_content-block--wide__copy">
		<h1>
			<?php echo $content_block_header; ?>
		</h1>
		<p>
			<?php echo $content_block_copy; ?>
		</p>
		<a href="<?php echo $content_block_cta['url']; ?>">
			<?php echo $content_block_cta['title']; ?>
		</a>
	</aside>
</section>