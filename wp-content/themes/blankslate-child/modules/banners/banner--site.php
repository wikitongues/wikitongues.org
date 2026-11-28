<?php
/**
 * Site banner (plan 1.4). Expects $site_banner, the row wt_site_banner() picked.
 *
 * @package blankslate-child
 */

if ( empty( $site_banner ) || ! is_array( $site_banner ) ) {
	return;
}
$wt_sb_type = 'form' === ( $site_banner['cta_type'] ?? '' ) ? 'form' : 'link';
?>
<aside class="wt_site-banner" aria-label="Announcement" data-site-banner="<?php echo esc_attr( (string) ( $site_banner['banner_id'] ?? '' ) ); ?>" data-site-banner-cta="<?php echo esc_attr( $wt_sb_type ); ?>">
	<div class="wt_site-banner__inner">
		<p class="wt_site-banner__message"><?php echo esc_html( (string) $site_banner['message'] ); ?></p>
		<a class="wt_site-banner__button" href="<?php echo esc_url( wt_site_banner_href( $site_banner ) ); ?>"<?php echo 'form' === $wt_sb_type ? ' data-cta-location="site-banner"' : ''; ?>>
			<?php echo esc_html( (string) ( $site_banner['cta_label'] ?? '' ) ); ?>
			<?php echo wt_icon( 'arrow-right-long' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</div>
</aside>
