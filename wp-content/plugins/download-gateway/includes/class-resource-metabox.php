<?php
/**
 * Resource_Metabox — shows the gateway download URL in the post editor.
 *
 * Registers a read-only metabox on all CPTs that have a registered
 * FileResolver. Content authors use this URL in links or the
 * [gateway_download] shortcode. The URL never changes for a given post ID.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Resource_Metabox {

	public static function register(): void {
		$post_types = FileResolverRegistry::registered_post_types();
		if ( empty( $post_types ) ) {
			return;
		}

		add_meta_box(
			'gateway_download_url',
			'Download Gateway',
			array( self::class, 'render' ),
			$post_types,
			'side',
			'default'
		);
	}

	public static function render( \WP_Post $post ): void {
		$url = rest_url( GATEWAY_REST_NAMESPACE . '/download/' . $post->ID );
		?>
		<p style="margin:0 0 6px;font-size:11px;color:#646970;">
			Use this URL wherever a download link is needed, or use the shortcode below.
		</p>
		<input
			type="text"
			readonly
			value="<?php echo esc_attr( $url ); ?>"
			style="width:100%;font-size:11px;"
			onclick="this.select();"
		/>
		<p style="margin:6px 0 0;font-size:11px;color:#646970;">
			Shortcode: <code>[gateway_download id="<?php echo esc_html( (string) $post->ID ); ?>"]</code>
		</p>
		<?php // @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php) ?>
		<?php if ( ! GATEWAY_ENABLED ) : ?>
		<p style="margin:6px 0 0;font-size:11px;color:#a00;">
			Gateway is disabled. Enable it in wp-config.php to activate this URL.
		</p>
		<?php endif; ?>
		<?php
	}
}
