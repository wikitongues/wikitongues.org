<?php
/**
 * Download_Shortcode — [gateway_download] shortcode.
 *
 * Usage:
 *   [gateway_download id="42"]
 *   [gateway_download id="42" label="Download PDF"]
 *
 * Renders an anchor tag pointing to the gateway download endpoint.
 * Falls back gracefully when the post ID is missing or invalid.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Download_Shortcode {

	public static function register(): void {
		add_shortcode( 'gateway_download', array( self::class, 'render' ) );
	}

	/**
	 * @param array<string,string>|string $atts
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'    => '',
				'label' => 'Download',
			),
			$atts,
			'gateway_download'
		);

		$post_id = (int) $atts['id'];
		if ( $post_id <= 0 ) {
			return '<!-- gateway_download: missing or invalid id -->';
		}

		$policy = PolicyResolver::resolve( $post_id );

		if ( SettingsRepository::POLICY_DISABLED === $policy ) {
			return '';
		}

		$url = rest_url( GATEWAY_REST_NAMESPACE . '/download/' . $post_id );

		return sprintf(
			'<a href="%s" class="gateway-download-link" data-post-id="%d" data-policy="%s">%s</a>',
			esc_url( $url ),
			$post_id,
			esc_attr( $policy ),
			esc_html( $atts['label'] )
		);
	}
}
