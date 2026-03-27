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

		// Webhook endpoint.
		$webhook_endpoint = isset( $_POST[ SettingsRepository::OPTION_WEBHOOK_ENDPOINT ] )
			? esc_url_raw( trim( (string) $_POST[ SettingsRepository::OPTION_WEBHOOK_ENDPOINT ] ) )
			: '';
		SettingsRepository::update_webhook_endpoint( $webhook_endpoint );

		// Data retention.
		$retention_months = isset( $_POST[ SettingsRepository::OPTION_RETENTION_MONTHS ] )
			? (int) $_POST[ SettingsRepository::OPTION_RETENTION_MONTHS ]
			: SettingsRepository::DEFAULT_RETENTION_MONTHS;

		if ( $retention_months <= 0 ) {
			$retention_months = SettingsRepository::DEFAULT_RETENTION_MONTHS;
		}

		update_option( SettingsRepository::OPTION_RETENTION_MONTHS, $retention_months );

		// Intake — global set.
		/** This filter is documented in download-gateway.php. */
		$registered_sets    = array_keys( (array) apply_filters( 'gateway_intake_fields', array() ) );
		$allowed_set_values = array_merge( array( 'none' ), $registered_sets );

		$global_intake_set = isset( $_POST[ SettingsRepository::OPTION_GLOBAL_INTAKE_SET ] )
			? sanitize_key( $_POST[ SettingsRepository::OPTION_GLOBAL_INTAKE_SET ] )
			: 'none';
		if ( ! in_array( $global_intake_set, $allowed_set_values, true ) ) {
			$global_intake_set = 'none';
		}
		update_option( SettingsRepository::OPTION_GLOBAL_INTAKE_SET, $global_intake_set );

		// Intake — global always.
		$global_intake_always = ! empty( $_POST[ SettingsRepository::OPTION_GLOBAL_INTAKE_ALWAYS ] ) ? '1' : '0';
		update_option( SettingsRepository::OPTION_GLOBAL_INTAKE_ALWAYS, $global_intake_always );

		// Intake — per-CPT set and always.
		foreach ( $post_types as $post_type ) {
			$set_key   = SettingsRepository::OPTION_CPT_INTAKE_SET_PREFIX . $post_type;
			$set_value = isset( $_POST[ $set_key ] ) ? sanitize_key( $_POST[ $set_key ] ) : '';
			// Empty = inherit (delete option); otherwise validate against allowed.
			if ( '' !== $set_value && ! in_array( $set_value, $allowed_set_values, true ) ) {
				$set_value = '';
			}
			SettingsRepository::update_cpt_intake_set( $post_type, $set_value );

			$always_key   = SettingsRepository::OPTION_CPT_INTAKE_ALWAYS_PREFIX . $post_type;
			$always_value = isset( $_POST[ $always_key ] ) ? sanitize_key( $_POST[ $always_key ] ) : '';
			if ( '1' === $always_value ) {
				SettingsRepository::update_cpt_intake_always( $post_type, true );
			} elseif ( '0' === $always_value ) {
				SettingsRepository::update_cpt_intake_always( $post_type, false );
			} else {
				SettingsRepository::update_cpt_intake_always( $post_type, null );
			}
		}
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
			SettingsRepository::POLICY_SOFT     => 'Skippable',
			SettingsRepository::POLICY_HARD     => 'Required',
			SettingsRepository::POLICY_DISABLED => 'Disabled',
			SettingsRepository::POLICY_INHERIT  => 'Inherit',
		);
		return $labels[ $policy ] ?? esc_html( $policy );
	}

	/** Render a <select> for an intake set field. */
	private static function intake_set_select( string $name, string $current, array $registered_sets, bool $include_inherit = true ): void {
		echo '<select name="' . esc_attr( $name ) . '">';
		if ( $include_inherit ) {
			echo '<option value="" ' . selected( $current, '', false ) . '>Inherit</option>';
		}
		echo '<option value="none" ' . selected( $current, 'none', false ) . '>None</option>';
		foreach ( $registered_sets as $set_name ) {
			echo '<option value="' . esc_attr( $set_name ) . '" ' . selected( $current, $set_name, false ) . '>' . esc_html( ucfirst( $set_name ) ) . '</option>';
		}
		echo '</select>';
	}

	/** Render a <select> for the intake always-show field. */
	private static function intake_always_select( string $name, string $current, bool $include_inherit = true ): void {
		echo '<select name="' . esc_attr( $name ) . '">';
		if ( $include_inherit ) {
			echo '<option value="" ' . selected( $current, '', false ) . '>Inherit</option>';
		}
		echo '<option value="0" ' . selected( $current, '0', false ) . '>First only</option>';
		echo '<option value="1" ' . selected( $current, '1', false ) . '>Every session</option>';
		echo '</select>';
	}

	/** Render a <select> for a policy field. */
	private static function policy_select( string $name, string $current, bool $include_inherit = true ): void {
		echo '<select name="' . esc_attr( $name ) . '">';
		if ( $include_inherit ) {
			echo '<option value="' . esc_attr( SettingsRepository::POLICY_INHERIT ) . '" ' . selected( $current, SettingsRepository::POLICY_INHERIT, false ) . '>Inherit</option>';
		}
		echo '<option value="none"     ' . selected( $current, 'none', false ) . '>None</option>';
		echo '<option value="soft"     ' . selected( $current, 'soft', false ) . '>Skippable</option>';
		echo '<option value="hard"     ' . selected( $current, 'hard', false ) . '>Required</option>';
		echo '<option value="disabled" ' . selected( $current, 'disabled', false ) . '>Disabled</option>';
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
		/** This filter is documented in download-gateway.php. */
		$registered_sets       = array_keys( (array) apply_filters( 'gateway_intake_fields', array() ) );
		$current_intake_set    = SettingsRepository::get_global_intake_set();
		$current_intake_always = SettingsRepository::get_global_intake_always() ? '1' : '0';
		$last_run              = get_option( RetentionJob::OPTION_LAST_RUN, null );
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
		<style>
			.gateway-legend { display:flex; flex-wrap:wrap; gap:1em 2em; margin:1em 0 0; padding:1em; background:#f6f7f7; border:1px solid #dcdcde; border-radius:3px; }
			.gateway-legend dt { font-weight:600; }
			.gateway-legend dd { margin:0 0 0 0.4em; display:inline; color:#50575e; }
			.gateway-legend span { display:flex; gap:0.3em; }
		</style>
		<div class="wrap">
			<h1>Download Gateway</h1>
			<p style="max-width:600px; color:#50575e;">
				Controls how visitors access downloadable resources — videos, captions, and documents.
				You can require visitors to share their name and email before downloading, ask follow-up questions, and automatically send that information to your CRM.
			</p>
			<?php // @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php) ?>
			<?php if ( ! GATEWAY_ENABLED ) : ?>
			<div class="notice notice-error inline" style="margin-bottom:1em;">
				<p><strong>Gateway is inactive.</strong> Downloads are not being tracked or gated. Contact your developer to enable it.</p>
			</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<div class="card" style="max-width:none; margin-bottom:1.5em;">
					<h2 class="title">Download access</h2>
					<p style="color:#50575e;">
						Set whether visitors must share their details to download, and whether to show a follow-up form asking how they plan to use the resource.
						The <strong>Global</strong> row is the default for all content. Per-content-type rows override it. Individual resources can be further customised from their edit screen.
						<?php if ( empty( $registered_sets ) ) : ?>
						<br /><em>No intake forms are currently configured.</em>
						<?php endif; ?>
					</p>

					<table class="widefat striped" style="margin-top:1em;">
						<thead>
							<tr>
								<th rowspan="2" style="vertical-align:bottom;">Content type</th>
								<th rowspan="2" style="vertical-align:bottom;">Access</th>
								<th colspan="2" style="text-align:center; border-bottom:1px solid #ccd0d4;">Follow-up form</th>
							</tr>
							<tr>
								<th>Form</th>
								<th>Ask every time?</th>
							</tr>
						</thead>
						<tbody>
							<tr style="background:#f0f6fc;">
								<td><strong>Global default</strong></td>
								<td><?php self::policy_select( SettingsRepository::OPTION_GLOBAL_GATE_POLICY, $current_policy, false ); ?></td>
								<td><?php self::intake_set_select( SettingsRepository::OPTION_GLOBAL_INTAKE_SET, $current_intake_set, $registered_sets, false ); ?></td>
								<td><?php self::intake_always_select( SettingsRepository::OPTION_GLOBAL_INTAKE_ALWAYS, $current_intake_always, false ); ?></td>
							</tr>
							<?php foreach ( $post_types as $post_type ) : ?>
								<?php
								$cpt_policy        = SettingsRepository::get_cpt_policy( $post_type ) ?? SettingsRepository::POLICY_INHERIT;
								$cpt_intake_set    = SettingsRepository::get_cpt_intake_set( $post_type ) ?? '';
								$cpt_intake_always = SettingsRepository::get_cpt_intake_always( $post_type );
								$cpt_always_value  = null === $cpt_intake_always ? '' : ( $cpt_intake_always ? '1' : '0' );
								$pt_obj            = get_post_type_object( $post_type );
								$pt_label          = $pt_obj ? $pt_obj->labels->name : ucwords( str_replace( '_', ' ', $post_type ) );
								?>
							<tr>
								<td><?php echo esc_html( $pt_label ); ?></td>
								<td><?php self::policy_select( 'gateway_cpt_policy_' . $post_type, $cpt_policy ); ?></td>
								<td><?php self::intake_set_select( SettingsRepository::OPTION_CPT_INTAKE_SET_PREFIX . $post_type, $cpt_intake_set, $registered_sets ); ?></td>
								<td><?php self::intake_always_select( SettingsRepository::OPTION_CPT_INTAKE_ALWAYS_PREFIX . $post_type, $cpt_always_value ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<dl class="gateway-legend">
						<span><dt>Inherit</dt><dd>— use the row above</dd></span>
						<span><dt>None</dt><dd>— open download, no prompt</dd></span>
						<span><dt>Skippable</dt><dd>— ask for details, visitor can decline</dd></span>
						<span><dt>Required</dt><dd>— email required before downloading</dd></span>
						<span><dt>Disabled</dt><dd>— download link hidden entirely</dd></span>
					</dl>
				</div>

				<div class="card" style="max-width:none; margin-bottom:1.5em;">
					<h2 class="title">Integrations</h2>

					<h3 style="margin-bottom:4px;">Dropbox</h3>
					<?php if ( SettingsRepository::dropbox_configured() ) : ?>
					<p class="description">&#10003; Connected. Video and caption files are served via Dropbox.</p>
					<?php else : ?>
					<p class="description">&#10007; Not connected &mdash; video and caption downloads will not work. Contact your developer to configure Dropbox.</p>
					<?php endif; ?>

					<h3 style="margin-top:1.5em; margin-bottom:4px;">CRM &amp; email notifications</h3>
					<p style="color:#50575e;">
						When someone downloads a resource or submits the follow-up form, the gateway can notify your CRM or email platform automatically.
						Paste the webhook URL from Make.com below. Leave blank to disable notifications.
					</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="gateway_webhook_endpoint">Notification URL</label>
							</th>
							<td>
								<input
									type="url"
									name="<?php echo esc_attr( SettingsRepository::OPTION_WEBHOOK_ENDPOINT ); ?>"
									id="gateway_webhook_endpoint"
									value="<?php echo esc_attr( SettingsRepository::get_webhook_endpoint() ); ?>"
									placeholder="https://hook.make.com/…"
									style="width:420px;"
								/>
								<p class="description">
									Notifies your CRM when a visitor registers, downloads a resource, or submits a follow-up form.
									<?php if ( '' !== SettingsRepository::get_webhook_endpoint() ) : ?>
									<span style="color:#0a0;">&#10003; Configured.</span>
									<?php endif; ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="card" style="max-width:none; margin-bottom:1.5em;">
					<h2 class="title">Data privacy</h2>
					<p style="color:#50575e;">
						To comply with data protection regulations, visitor names and email addresses are automatically deleted after the period below.
						Download history is preserved so usage data remains intact — only personal identifiers are removed.
					</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="gateway_retention_months">Delete personal data after</label>
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
								<p class="description">Recommended: 24 months. Names and email addresses older than this are automatically removed once per day.</p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button( 'Save settings' ); ?>
			</form>

			<div class="card" style="max-width:none; margin-bottom:1.5em;">
				<h2 class="title">Individual resource overrides</h2>
				<p style="color:#50575e;">
					These resources have a custom access setting that differs from their content type default.
					To change a resource's setting, open it in the editor and look for the Download Gateway panel in the sidebar.
				</p>
				<?php if ( empty( $audit_rows ) ) : ?>
				<p><em>No overrides set — all resources use their content type or global default.</em></p>
				<?php else : ?>
				<table class="widefat striped" style="margin-top:1em;">
					<thead>
						<tr>
							<th>Resource</th>
							<th>Content type</th>
							<th>Access</th>
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
							<td>
								<?php
								$pt_obj = get_post_type_object( $row['post_type'] );
								echo esc_html( $pt_obj ? $pt_obj->labels->name : ucwords( str_replace( '_', ' ', $row['post_type'] ) ) );
								?>
							</td>
							<td><?php echo esc_html( self::policy_label( $row['policy'] ) ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>

			<div class="card" style="max-width:none; margin-bottom:1.5em;">
				<h2 class="title">Privacy cleanup</h2>
				<p style="color:#50575e;">
					This runs automatically once per day and removes personal data older than your retention period.
					You can also run it manually below — for example, after updating the retention period.
				</p>

				<?php if ( null !== $retention_result ) : ?>
				<div class="notice notice-success inline">
					<p>Cleanup complete &mdash; <strong><?php echo esc_html( (string) $retention_result ); ?></strong> subscriber record(s) anonymized.</p>
				</div>
				<?php endif; ?>

				<p>
					<?php if ( is_array( $last_run ) ) : ?>
					Last run: <strong><?php echo esc_html( $last_run['timestamp'] ?? '—' ); ?></strong>
					&mdash; <?php echo esc_html( (string) ( $last_run['count'] ?? 0 ) ); ?> record(s) anonymized.
					<?php else : ?>
					<em>Has not run yet.</em>
					<?php endif; ?>
				</p>

				<form method="post">
					<?php wp_nonce_field( self::NONCE_ACTION_RUN_NOW, self::NONCE_FIELD_RUN_NOW ); ?>
					<?php submit_button( 'Run cleanup now', 'secondary' ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
