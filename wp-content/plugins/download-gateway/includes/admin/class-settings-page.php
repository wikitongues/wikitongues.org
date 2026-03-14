<?php
/**
 * Settings_Page — admin UI for the download gateway.
 *
 * Sub-phase 0: registers the menu entry and renders a placeholder.
 * Sub-phases 2a/2b will add real fields (global gate policy, retention months,
 * Dropbox credentials, GA4 measurement ID, etc.).
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Settings_Page {

	public static function register(): void {
		add_options_page(
			'Download Gateway',
			'Download Gateway',
			'manage_options',
			'download-gateway',
			[ self::class, 'render' ]
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1>Download Gateway</h1>
			<p>
				Version <?php echo esc_html( DG_VERSION ); ?>
				&nbsp;&mdash;&nbsp;
				Status: <strong><?php echo DG_ENABLED ? '<span style="color:#0a0;">Enabled</span>' : '<span style="color:#a00;">Disabled</span>'; ?></strong>
			</p>
			<?php if ( ! DG_ENABLED ) : ?>
			<div class="notice notice-warning inline">
				<p>
					The gateway is not yet intercepting downloads.
					Add <code>define( 'DG_ENABLED', true );</code> to <code>wp-config.php</code> to enable it.
				</p>
			</div>
			<?php endif; ?>
			<hr>
			<p style="color:#666;">
				Gateway settings (global gate policy, retention period, Dropbox credentials,
				GA4 measurement ID) will appear here in a future release.
			</p>
		</div>
		<?php
	}
}
