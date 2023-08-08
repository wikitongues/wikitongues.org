<?php
if ( is_page_template('template-revitalization-fellows.php') ) {
	$content_block_image = get_field('fellow_image');
	$first_name = get_field('first_name');
	$last_name = get_field('last_name');
	$content_block_header = $first_name . ' ' . $last_name;
	$fellow_language = get_field('fellow_language');
	$fellow_location = get_field('fellow_location');
	$content_block_cta = get_field('content_block_cta');
} else {
	$content_block_image = get_sub_field('content_block_image');
	$content_block_header = get_sub_field('content_block_header');
	$content_block_copy = get_sub_field('content_block_copy');
	$content_block_cta = get_sub_field('content_block_cta');
}
?>

<section class="wt_content-block--thirds">
	<aside class="wt_content-block--thirds__image" role="img" aria-label="<?php echo $content_block_image['alt']; ?>" style="background-image:url(<?php echo $content_block_image['url']; ?>);"></aside>
	<aside class="wt_content-block--thirds__copy">
		<h1>
			<?php echo $content_block_header; ?>
		</h1>
		<?php if ( is_page_template( 'template-revitalization-fellows.php' ) ): ?>
			<p>
				<strong><?php echo $fellow_language; ?></strong><br />
				<span><?php echo $fellow_location; ?></span>
			</p>
		<?php else: ?>
			<p>
				<?php echo $content_block_copy; ?>
			</p>
		<?php endif; ?>
		<a href="<?php echo $content_block_cta['url']; ?>">
			<?php echo $content_block_cta['title']; ?>
		</a>
	</aside>
</section>