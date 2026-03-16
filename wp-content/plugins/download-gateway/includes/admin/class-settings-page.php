<?php
/**
 * Settings_Page — admin UI for the download gateway.
 *
 * Settings → Download Gateway. Exposes the global gate policy, the data
 * retention window, and a manual run-now button for the retention job.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Settings_Page {

	private const NONCE_ACTION         = 'gateway_settings_save';
	private const NONCE_FIELD          = 'gateway_settings_nonce';
	private const NONCE_ACTION_RUN_NOW = 'gateway_retention_run';
	private const NONCE_FIELD_RUN_NOW  = 'gateway_retention_nonce';

	public static function register(): void {
		add_options_page(
			'Download Gateway',
			'Download Gateway',
			'manage_options',
			'download-gateway',
			array( self::class, 'render' )
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

		$allowed = array( 'none', 'soft', 'hard' );
		$policy  = isset( $_POST[ SettingsRepository::OPTION_GLOBAL_GATE_POLICY ] )
			? sanitize_key( $_POST[ SettingsRepository::OPTION_GLOBAL_GATE_POLICY ] )
			: SettingsRepository::DEFAULT_GLOBAL_GATE_POLICY;

		if ( ! in_array( $policy, $allowed, true ) ) {
			$policy = SettingsRepository::DEFAULT_GLOBAL_GATE_POLICY;
		}

		update_option( SettingsRepository::OPTION_GLOBAL_GATE_POLICY, $policy );

		$retention_months = isset( $_POST[ SettingsRepository::OPTION_RETENTION_MONTHS ] )
			? (int) $_POST[ SettingsRepository::OPTION_RETENTION_MONTHS ]
			: SettingsRepository::DEFAULT_RETENTION_MONTHS;

		if ( $retention_months <= 0 ) {
			$retention_months = SettingsRepository::DEFAULT_RETENTION_MONTHS;
		}

		update_option( SettingsRepository::OPTION_RETENTION_MONTHS, $retention_months );
	}

	/**
	 * Handle the run-now POST on admin_init — fires before any output so
	 * wp_safe_redirect() can send the Location header cleanly.
	 *
	 * Hooked in download-gateway.php via add_action( 'admin_init', ... ).
	 */
	public static function handle_run_now_action(): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD_RUN_NOW ] ) ) {
			return;
		}

		if (
			! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_FIELD_RUN_NOW ] ), self::NONCE_ACTION_RUN_NOW )
			|| ! current_user_can( 'manage_options' )
		) {
			wp_die( 'Unauthorized.', 403 );
		}

		$count = RetentionJob::anonymize();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'download-gateway',
					'retention_result' => $count,
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle settings form submission.
		if ( isset( $_POST[ self::NONCE_FIELD ] ) ) {
			self::save();
		}

		$current_policy           = SettingsRepository::get_global_gate_policy();
		$current_retention_months = SettingsRepository::get_retention_months();
		$last_run                 = get_option( RetentionJob::OPTION_LAST_RUN, null );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$retention_result = isset( $_GET['retention_result'] ) ? (int) $_GET['retention_result'] : null;
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
					<tr>
						<th scope="row">
							<label for="gateway_retention_months">Data retention</label>
						</th>
						<td>
							<input
								type="number"
								name="<?php echo esc_attr( SettingsRepository::OPTION_RETENTION_MONTHS ); ?>"
								id="gateway_retention_months"
								value="<?php echo esc_attr( (string) $current_retention_months ); ?>"
								min="1"
								max="120"
								style="width:80px;"
							/> months
							<p class="description">
								Email and name are nulled out after this many months.
								The person record itself is kept so download history remains intact.
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Save settings' ); ?>
			</form>

			<hr />

			<h2>Retention</h2>

			<?php if ( null !== $retention_result ) : ?>
			<div class="notice notice-success inline">
				<p>Retention job completed: <strong><?php echo esc_html( (string) $retention_result ); ?></strong> record(s) anonymized.</p>
			</div>
			<?php endif; ?>

			<?php if ( is_array( $last_run ) ) : ?>
			<p>
				Last run: <strong><?php echo esc_html( $last_run['timestamp'] ?? '—' ); ?></strong>
				&mdash; <?php echo esc_html( (string) ( $last_run['count'] ?? 0 ) ); ?> record(s) anonymized.
			</p>
			<?php else : ?>
			<p>The retention job has not run yet.</p>
			<?php endif; ?>

			<p>
				The retention job also runs automatically once per day via WP-Cron.
				On production, back it up with a server cron:
				<code>wp cron event run --due-now</code>
			</p>

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION_RUN_NOW, self::NONCE_FIELD_RUN_NOW ); ?>
				<?php submit_button( 'Run retention now', 'secondary' ); ?>
			</form>
		</div>
		<?php
	}
}
