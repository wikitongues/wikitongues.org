<?php
/**
 * Settings_Page — admin UI for the download gateway.
 *
 * Settings → Download Gateway. Currently exposes the global gate policy
 * (the site-wide default that PolicyResolver falls back to when no
 * per-resource or per-taxonomy override is set).
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Settings_Page {

	private const NONCE_ACTION = 'gateway_settings_save';
	private const NONCE_FIELD  = 'gateway_settings_nonce';

	public static function register(): void {
		add_options_page(
			'Download Gateway',
			'Download Gateway',
			'manage_options',
			'download-gateway',
			[ self::class, 'render' ]
		);
	}

	public static function save(): void {
		if (
			! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_FIELD ] ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$allowed = [ 'none', 'soft', 'hard' ];
		$policy  = isset( $_POST[ SettingsRepository::OPTION_GLOBAL_GATE_POLICY ] )
			? sanitize_key( $_POST[ SettingsRepository::OPTION_GLOBAL_GATE_POLICY ] )
			: SettingsRepository::DEFAULT_GLOBAL_GATE_POLICY;

		if ( ! in_array( $policy, $allowed, true ) ) {
			$policy = SettingsRepository::DEFAULT_GLOBAL_GATE_POLICY;
		}

		update_option( SettingsRepository::OPTION_GLOBAL_GATE_POLICY, $policy );
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission directly (no options-general.php redirect needed).
		if ( isset( $_POST[ self::NONCE_FIELD ] ) ) {
			self::save();
		}

		$current_policy = SettingsRepository::get_global_gate_policy();
		?>
		<div class="wrap">
			<h1>Download Gateway</h1>
			<p>
				Version <?php echo esc_html( GATEWAY_VERSION ); ?>
				&nbsp;&mdash;&nbsp;
				<?php // @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php) ?>
				Status: <strong><?php echo GATEWAY_ENABLED ? '<span style="color:#0a0;">Enabled</span>' : '<span style="color:#a00;">Disabled</span>'; ?></strong>
			</p>
			<?php // @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php) ?>
			<?php if ( ! GATEWAY_ENABLED ) : ?>
			<div class="notice notice-warning inline">
				<p>
					The gateway is not yet intercepting downloads.
					Add <code>define( 'GATEWAY_ENABLED', true );</code> to <code>wp-config.php</code> to enable it.
				</p>
			</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="gateway_global_gate_policy">Global gate policy</label>
						</th>
						<td>
							<select name="<?php echo esc_attr( SettingsRepository::OPTION_GLOBAL_GATE_POLICY ); ?>" id="gateway_global_gate_policy">
								<option value="none" <?php selected( $current_policy, 'none' ); ?>>None — direct redirect, no gate</option>
								<option value="soft" <?php selected( $current_policy, 'soft' ); ?>>Soft gate — skippable email prompt</option>
								<option value="hard" <?php selected( $current_policy, 'hard' ); ?>>Hard gate — email required</option>
							</select>
							<p class="description">
								Site-wide default. Individual resources can override this via the
								<strong>Download Gateway</strong> metabox in the post editor.
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Save settings' ); ?>
			</form>
		</div>
		<?php
	}
}
