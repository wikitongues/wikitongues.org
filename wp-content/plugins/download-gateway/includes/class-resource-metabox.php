<?php
/**
 * Resource_Metabox — single "Download Gateway" sidebar metabox.
 *
 * Consolidates the gate policy override select and the download URL/shortcode
 * reference into one panel. Saves _gateway_gate_policy via save_post so there
 * is no ACF dependency for this field.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Resource_Metabox {

	private const NONCE_ACTION = 'gateway_resource_save';
	private const NONCE_FIELD  = 'gateway_resource_nonce';

	public static function register(): void {
		$post_types = FileResolverRegistry::registered_post_types();
		if ( empty( $post_types ) ) {
			return;
		}

		add_meta_box(
			'gateway_resource',
			'Download Gateway',
			array( self::class, 'render' ),
			$post_types,
			'side',
			'default'
		);
	}

	public static function save( int $post_id ): void {
		if (
			! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_FIELD ] ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$allowed = array_merge( array( '' ), SettingsRepository::allowed_override_values() );
		$value   = isset( $_POST['_gateway_gate_policy'] )
			? sanitize_key( $_POST['_gateway_gate_policy'] )
			: '';

		if ( ! in_array( $value, $allowed, true ) ) {
			$value = '';
		}

		update_post_meta( $post_id, '_gateway_gate_policy', $value );

		// Intake set — free-form set name, 'none', 'inherit', or empty.
		$intake_set = isset( $_POST['_gateway_intake_set'] )
			? sanitize_key( $_POST['_gateway_intake_set'] )
			: 'inherit';
		update_post_meta( $post_id, IntakeResolver::META_KEY_SET, $intake_set );

		// Intake always — '1', '0', or 'inherit'.
		$intake_always_allowed = array( '1', '0', 'inherit' );
		$intake_always         = isset( $_POST['_gateway_intake_always'] )
			? sanitize_key( $_POST['_gateway_intake_always'] )
			: 'inherit';
		if ( ! in_array( $intake_always, $intake_always_allowed, true ) ) {
			$intake_always = 'inherit';
		}
		update_post_meta( $post_id, IntakeResolver::META_KEY_ALWAYS, $intake_always );
	}

	public static function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$policy        = (string) get_post_meta( $post->ID, '_gateway_gate_policy', true );
		$intake_set    = (string) get_post_meta( $post->ID, IntakeResolver::META_KEY_SET, true );
		$intake_always = (string) get_post_meta( $post->ID, IntakeResolver::META_KEY_ALWAYS, true );
		$url           = rest_url( GATEWAY_REST_NAMESPACE . '/download/' . $post->ID );

		/** This filter is documented in download-gateway.php. */
		$registered_sets = array_keys( (array) apply_filters( 'gateway_intake_fields', array() ) );
		?>
		<p style="margin:0 0 4px;font-size:11px;font-weight:600;">Gate policy</p>
		<select name="_gateway_gate_policy" style="width:100%;margin-bottom:10px;">
			<option value=""         <?php selected( $policy, '' ); ?>>Inherit</option>
			<option value="none"     <?php selected( $policy, 'none' ); ?>>None</option>
			<option value="soft"     <?php selected( $policy, 'soft' ); ?>>Skippable</option>
			<option value="hard"     <?php selected( $policy, 'hard' ); ?>>Required</option>
			<option value="disabled" <?php selected( $policy, 'disabled' ); ?>>Disabled</option>
		</select>
		<p style="margin:0 0 4px;font-size:11px;font-weight:600;">Intake form</p>
		<select name="_gateway_intake_set" style="width:100%;margin-bottom:6px;">
			<option value="inherit" <?php selected( $intake_set, 'inherit' ); ?>>Inherit</option>
			<option value="none"    <?php selected( $intake_set, 'none' ); ?>>None</option>
			<?php foreach ( $registered_sets as $set_name ) : ?>
			<option value="<?php echo esc_attr( $set_name ); ?>" <?php selected( $intake_set, $set_name ); ?>><?php echo esc_html( ucfirst( $set_name ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<p style="margin:0 0 4px;font-size:11px;font-weight:600;">Show intake on repeat downloads</p>
		<select name="_gateway_intake_always" style="width:100%;margin-bottom:10px;">
			<option value="inherit" <?php selected( $intake_always, 'inherit' ); ?>>Inherit</option>
			<option value="0"       <?php selected( $intake_always, '0' ); ?>>First only</option>
			<option value="1"       <?php selected( $intake_always, '1' ); ?>>Every session</option>
		</select>
		<p style="margin:0 0 4px;font-size:11px;font-weight:600;">Download URL</p>
		<input
			type="text"
			readonly
			value="<?php echo esc_attr( $url ); ?>"
			style="width:100%;font-size:11px;margin-bottom:6px;"
			onclick="this.select();"
		/>
		<p style="margin:0 0 4px;font-size:11px;font-weight:600;">Shortcode</p>
		<input
			type="text"
			readonly
			value="[gateway_download id=&quot;<?php echo esc_attr( (string) $post->ID ); ?>&quot;]"
			style="width:100%;font-size:11px;margin-bottom:6px;cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
			onclick="navigator.clipboard.writeText(this.value);this.select();"
			title="Click to copy"
		/>
		<?php // @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php) ?>
		<?php if ( ! GATEWAY_ENABLED ) : ?>
		<p style="margin:4px 0 0;font-size:11px;color:#a00;">
			Gateway is disabled — enable in wp-config.php to activate this URL.
		</p>
		<?php endif; ?>
		<?php
	}
}
