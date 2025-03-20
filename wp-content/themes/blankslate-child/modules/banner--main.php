<?php
// universal banner variables; $banner_image is defined per template/page

$banner_image = $page_banner['banner_image'];
$selected_file = get_field('selected_file');
$display_caption = $page_banner['display_caption'] ?? '';

if($selected_file){
	$banner_cta = [
		'url' => $file_field,
		'title' => $file_cta
	];
} else {
	$banner_cta = $page_banner['banner_cta'] ?? false;
}

$principal_file = $page_banner['principal_file'] ?? false;
if ($principal_file) {
	$file_field = get_field('file', $principal_file->ID);
}

$banner_cta_placeholder = $page_banner['banner_cta_placeholder'] ?? false;
?>

<div class="wt_banner" role="img" aria-label="<?php echo $banner_image['alt']; ?>" style="background-image:url(<?php echo $banner_image['url']; ?>);">
	<div class="wt_banner__copy">
		<h1 class="wt_text--header">
			<?php echo $page_banner['banner_header']; ?>
		</h1>
		<?php echo wpautop(wp_kses_post($page_banner['banner_copy'])) ?>
		<?php if ( $banner_cta ): ?>
			<a href="<?php echo $banner_cta['url']; ?>">
				<?php echo $banner_cta['title']; ?>
			</a>
		<?php elseif ( $banner_cta_placeholder ): ?>
			<strong>
				<?php echo $banner_cta_placeholder; ?>
			</strong>
		<?php endif; ?>
	</div>
	<?php if (!empty($banner_image['caption'])  && $display_caption === 'Yes'): ?>
		<p class="caption"><strong><?php echo $banner_image['caption']; ?></strong></p>
	<?php endif; ?>
</div>