<?php
/**
 * Settings_Page — admin UI for the download gateway.
 *
 * Settings → Download Gateway. Exposes:
 *   - Global gate policy (site-wide fallback)
 *   - Per-CPT gate policy (one row per registered post type)
 *   - Data retention window
 *   - Manual run-now button for the retention job
 *   - Audit table of all per-resource policy overrides
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

		// Global gate policy.
		$global_policy = isset( $_POST[ SettingsRepository::OPTION_GLOBAL_GATE_POLICY ] )
			? sanitize_key( $_POST[ SettingsRepository::OPTION_GLOBAL_GATE_POLICY ] )
			: SettingsRepository::DEFAULT_GLOBAL_GATE_POLICY;

		if ( ! in_array( $global_policy, SettingsRepository::concrete_policies(), true ) ) {
			$global_policy = SettingsRepository::DEFAULT_GLOBAL_GATE_POLICY;
		}

		update_option( SettingsRepository::OPTION_GLOBAL_GATE_POLICY, $global_policy );

		// Per-CPT policies.
		/** This filter is documented in Settings_Page::render(). */
		$post_types = (array) apply_filters( 'gateway_policy_post_types', FileResolverRegistry::registered_post_types() );
		foreach ( $post_types as $post_type ) {
			$key   = 'gateway_cpt_policy_' . $post_type;
			$value = isset( $_POST[ $key ] ) ? sanitize_key( $_POST[ $key ] ) : SettingsRepository::POLICY_INHERIT;
			SettingsRepository::update_cpt_policy( $post_type, $value );
		}

		// Data retention.
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

	/**
	 * Query wp_postmeta for all posts with a non-empty per-resource policy override.
	 *
	 * @return array<int,array{post_id:int,post_title:string,post_type:string,policy:string}>
	 */
	private static function get_override_audit_rows(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT pm.post_id, p.post_title, p.post_type, pm.meta_value AS policy
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s
				   AND pm.meta_value != ''
				   AND pm.meta_value != %s
				 ORDER BY p.post_type, p.post_title",
				PolicyResolver::META_KEY,
				SettingsRepository::POLICY_INHERIT
			)
		);

		if ( ! $rows ) {
			return array();
		}

		$result = array();
		foreach ( $rows as $row ) {
			$result[] = array(
				'post_id'    => (int) $row->post_id,
				'post_title' => (string) $row->post_title,
				'post_type'  => (string) $row->post_type,
				'policy'     => (string) $row->policy,
			);
		}
		return $result;
	}

	/** Render a human-readable policy label. */
	private static function policy_label( string $policy ): string {
		$labels = array(
			SettingsRepository::POLICY_NONE     => 'None',
			SettingsRepository::POLICY_SOFT     => 'Soft gate',
			SettingsRepository::POLICY_HARD     => 'Hard gate',
			SettingsRepository::POLICY_DISABLED => 'Disabled',
			SettingsRepository::POLICY_INHERIT  => 'Inherit',
		);
		return $labels[ $policy ] ?? esc_html( $policy );
	}

	/** Render a <select> for a policy field. */
	private static function policy_select( string $name, string $current, bool $include_inherit = true ): void {
		echo '<select name="' . esc_attr( $name ) . '">';
		if ( $include_inherit ) {
			echo '<option value="' . esc_attr( SettingsRepository::POLICY_INHERIT ) . '" ' . selected( $current, SettingsRepository::POLICY_INHERIT, false ) . '>Inherit</option>';
		}
		echo '<option value="none" ' . selected( $current, 'none', false ) . '>None — direct redirect</option>';
		echo '<option value="soft" ' . selected( $current, 'soft', false ) . '>Soft gate — skippable prompt</option>';
		echo '<option value="hard" ' . selected( $current, 'hard', false ) . '>Hard gate — email required</option>';
		echo '<option value="disabled" ' . selected( $current, 'disabled', false ) . '>Disabled — hide download link</option>';
		echo '</select>';
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
		/**
		 * Filters the post types shown in the per-CPT policy settings table.
		 *
		 * By default includes all post types with a registered FileResolver.
		 * Add additional CPTs here before their FileResolver is built so that
		 * policy can be configured in advance.
		 *
		 * @param string[] $post_types Array of post type slugs.
		 */
		$post_types = (array) apply_filters( 'gateway_policy_post_types', FileResolverRegistry::registered_post_types() );
		$audit_rows = self::get_override_audit_rows();
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

			<h2 class="title">Dropbox integration</h2>
			<?php if ( SettingsRepository::dropbox_configured() ) : ?>
			<p class="description">&#10003; Dropbox credentials found in wp-config.php. Test by downloading a video.</p>
			<?php else : ?>
			<p class="description">&#10007; Dropbox not configured &mdash; define <code>GATEWAY_DROPBOX_APP_KEY</code>, <code>GATEWAY_DROPBOX_APP_SECRET</code>, and <code>GATEWAY_DROPBOX_REFRESH_TOKEN</code> in wp-config.php.</p>
			<?php endif; ?>

			<hr />

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<h2 class="title">Gate policy</h2>
				<p>
					Policies resolve in order: per-resource override → per-CPT default → global default.<br />
					<em>Disabled</em> hides the download link entirely. <em>Inherit</em> falls through to the next tier.
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Global default</th>
						<td>
							<?php self::policy_select( SettingsRepository::OPTION_GLOBAL_GATE_POLICY, $current_policy, false ); ?>
							<p class="description">Site-wide fallback when no per-CPT or per-resource override is set.</p>
						</td>
					</tr>

					<?php foreach ( $post_types as $post_type ) : ?>
						<?php $cpt_policy = SettingsRepository::get_cpt_policy( $post_type ) ?? SettingsRepository::POLICY_INHERIT; ?>
					<tr>
						<th scope="row"><?php echo esc_html( $post_type ); ?></th>
						<td>
							<?php self::policy_select( 'gateway_cpt_policy_' . $post_type, $cpt_policy ); ?>
						</td>
					</tr>
					<?php endforeach; ?>

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

			<h2>Per-resource overrides</h2>

			<?php if ( empty( $audit_rows ) ) : ?>
			<p>No per-resource policy overrides are set. All resources use their CPT or global default.</p>
			<?php else : ?>
			<table class="widefat striped" style="max-width:700px;">
				<thead>
					<tr>
						<th>Post</th>
						<th>Type</th>
						<th>Policy</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $audit_rows as $row ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $row['post_id'] ) ); ?>">
								<?php echo esc_html( $row['post_title'] ?: '(no title)' ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $row['post_type'] ); ?></td>
						<td><?php echo esc_html( self::policy_label( $row['policy'] ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>

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
