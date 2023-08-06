<?php
// vars
$front_page_banner = get_field('front_page_banner');
$banner_image = $front_page_banner['banner_image'];
$banner_header = $front_page_banner['banner_image'];
$banner_copy = $front_page_banner['banner_copy'];
$banner_CTA = $front_page_banner['banner_CTA'];
$banner_CTA_placeholder = $front_page_banner['banner_CTA_placeholder']; 
?>

<div class="wt_banner" role="img" aria-label="<?php echo $banner_image['alt']; ?>" style="background-image:url(<?php echo $banner_image['url']; ?>);">
	<div class="wt_banner__copy">
		<h1 class="wt_text--header">
			<?php echo $banner_header; ?>
		</h1>
		<p class="wt_text--body">
			<?php echo $banner_copy; ?>
		</p>
	</div>
</div>