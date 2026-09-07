<?php
$banner_image    = $page_banner['banner_image'];
$banner_label    = $banner_image['alt'] ?? '';
$banner_url      = $banner_image['url'] ?? '';
$selected_file   = get_field( 'selected_file' );
$display_caption = $page_banner['display_caption'] ?? '';
$banner_copy     = wpautop( wp_kses_post( $page_banner['banner_copy'] ) );
$banner_header   = $page_banner['banner_header'];

$gateway_active     = shortcode_exists( 'gateway_download' ) && GATEWAY_ENABLED;
$banner_gateway_url = null; // Set below when gateway should handle the CTA.

if ( $selected_file && $gateway_active ) {
	$bg_policy = \WT\DownloadGateway\PolicyResolver::resolve( $selected_file->ID );
	if ( $bg_policy !== \WT\DownloadGateway\SettingsRepository::POLICY_DISABLED ) {
		$bg_intake          = \WT\DownloadGateway\IntakeResolver::resolve( $selected_file->ID );
		$banner_gateway_url = rest_url( GATEWAY_REST_NAMESPACE . '/download/' . $selected_file->ID );
	}
	$banner_cta = null;
} elseif ( $selected_file ) {
	$bg_policy  = null;
	$bg_intake  = null;
	$banner_cta = array(
		'url'   => $file_field,
		'title' => $file_cta,
	);
} else {
	$bg_policy  = null;
	$bg_intake  = null;
	$banner_cta = $page_banner['banner_cta'] ?? false;
}

$principal_file = $page_banner['principal_file'] ?? false;
if ( $principal_file ) {
	$file_field = get_field( 'file', $principal_file->ID );
}

$banner_cta_placeholder = $page_banner['banner_cta_placeholder'] ?? false;
?>

<div class="wt_banner" role="img" aria-label="<?php echo esc_html( $banner_label ); ?>" style="background-image:url(<?php echo esc_html( $banner_url ); ?>);">
	<div class="wt_banner__copy">
		<h1 class="wt_text--header"><?php echo $banner_header; ?></h1>
		<?php echo $banner_copy; ?>
		<?php if ( $banner_gateway_url ) : ?>
			<a href="<?php echo esc_url( $banner_gateway_url ); ?>"
				class="gateway-download-link"
				data-post-id="<?php echo esc_attr( $selected_file->ID ); ?>"
				data-policy="<?php echo esc_attr( $bg_policy ); ?>"
				data-post-type="document_files"
				data-intake-set="<?php echo esc_attr( $bg_intake['set'] ?? '' ); ?>"
				data-intake-always="<?php echo ( $bg_intake['always'] ?? false ) ? '1' : '0'; ?>"
				data-download-source="banner">
				<?php echo esc_html( $file_cta ); ?>
			</a>
		<?php elseif ( $banner_cta ) : ?>
			<a href="<?php echo $banner_cta['url']; ?>">
				<?php echo $banner_cta['title']; ?>
			</a>
		<?php elseif ( $banner_cta_placeholder ) : ?>
			<strong>
				<?php echo $banner_cta_placeholder; ?>
			</strong>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $banner_image['caption'] ) && $display_caption === 'Yes' ) : ?>
		<p class="caption"><strong><?php echo $banner_image['caption']; ?></strong></p>
	<?php endif; ?>
</div>