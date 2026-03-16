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

		$allowed = array( '', 'none', 'soft', 'hard' );
		$value   = isset( $_POST['_gateway_gate_policy'] )
			? sanitize_key( $_POST['_gateway_gate_policy'] )
			: '';

		if ( ! in_array( $value, $allowed, true ) ) {
			$value = '';
		}

		update_post_meta( $post_id, '_gateway_gate_policy', $value );
	}

	public static function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$policy = (string) get_post_meta( $post->ID, '_gateway_gate_policy', true );
		$url    = rest_url( GATEWAY_REST_NAMESPACE . '/download/' . $post->ID );
		?>
		<p style="margin:0 0 4px;font-size:11px;font-weight:600;">Gate policy</p>
		<select name="_gateway_gate_policy" style="width:100%;margin-bottom:10px;">
			<option value=""  <?php selected( $policy, '' ); ?>>Inherit (use global default)</option>
			<option value="none" <?php selected( $policy, 'none' ); ?>>None — direct redirect</option>
			<option value="soft" <?php selected( $policy, 'soft' ); ?>>Soft gate — skippable prompt</option>
			<option value="hard" <?php selected( $policy, 'hard' ); ?>>Hard gate — email required</option>
		</select>
		<p style="margin:0 0 4px;font-size:11px;font-weight:600;">Download URL</p>
		<input
			type="text"
			readonly
			value="<?php echo esc_attr( $url ); ?>"
			style="width:100%;font-size:11px;margin-bottom:6px;"
			onclick="this.select();"
		/>
		<p style="margin:0 0 6px;font-size:11px;color:#646970;">
			Shortcode: <code>[gateway_download id="<?php echo esc_html( (string) $post->ID ); ?>"]</code>
		</p>
		<?php // @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php) ?>
		<?php if ( ! GATEWAY_ENABLED ) : ?>
		<p style="margin:4px 0 0;font-size:11px;color:#a00;">
			Gateway is disabled — enable in wp-config.php to activate this URL.
		</p>
		<?php endif; ?>
		<?php
	}
}
