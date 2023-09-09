<?php
// universal banner variables; $banner_image is defined per template/page
$banner_image = $page_banner['banner_image'];
$banner_header = $page_banner['banner_header'];
$banner_copy = $page_banner['banner_copy'];
$banner_cta = $page_banner['banner_cta'];
$banner_cta_placeholder = $page_banner['banner_cta_placeholder']; 
?>

<div class="wt_banner" role="img" aria-label="<?php echo $banner_image['alt']; ?>" style="background-image:url(<?php echo $banner_image['url']; ?>);">
	<div class="wt_banner__copy">
		<h1 class="wt_text--header .SchnyderWideM-Light-Web">
			<?php echo $banner_header; ?>
		</h1>
		<p class="wt_text--body">
			<?php echo $banner_copy; ?>
		</p>
		<?php if ( $banner_cta ): ?>
			<a class="wt_button--large" href="<?php echo $banner_cta['url']; ?>">
				<?php echo $banner_cta['title']; ?>
				<i class="fa-regular fa-arrow-right-long"></i>
			</a>
		<?php elseif ( $banner_cta_placeholder ): ?>
			<p class="wt_text--body">
				<strong>
					<?php echo $banner_cta_placeholder; ?>
				</strong>
			</p>
		<?php else: ?>
			<!-- there is no CTA or helper text to display -->
		<?php endif; ?>
	</div>
</div>
<div class="wt_banner__copy--mobile">
	<h1 class="wt_text--header .SchnyderWideM-Light-Web">
		<?php echo $banner_header; ?>
	</h1>
	<p class="wt_text--body">
		<?php echo $banner_copy; ?>
	</p>
	<?php if ( $banner_cta ): ?>
		<a class="wt_button--large" href="<?php echo $banner_cta['url']; ?>">
			<?php echo $banner_cta['title']; ?>
			<i class="fa-regular fa-arrow-right-long"></i>
		</a>
	<?php elseif ( $banner_cta_placeholder ): ?>
		<p class="wt_text--body">
			<strong>
				<?php echo $banner_cta_placeholder; ?>
			</strong>
		</p>
	<?php else: ?>
		<!-- there is no CTA or helper text to display -->
	<?php endif; ?>
</div>