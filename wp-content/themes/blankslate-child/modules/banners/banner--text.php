<?php
/*
 * Text-only banner, used by the People and Partners templates. The
 * image-backed banner is modules/banners/banner--main.php.
 *
 * Callers set $text_banner to an ACF group with banner_header/banner_copy.
 */
$banner_header = $text_banner['banner_header'] ?? '';
$banner_copy   = $text_banner['banner_copy'] ?? '';
?>

<div class="wt_banner--text">
	<h1>
		<?php echo $banner_header; ?>
	</h1>
	<p>
		<?php echo $banner_copy; ?>
	</p>
</div>