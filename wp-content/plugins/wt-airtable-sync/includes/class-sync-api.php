<?php
/**
 * Sync_API — REST route registration and request handling.
 *
 * Registers: POST /wp-json/wikitongues/v1/sync/{post_type}
 *
 * Authentication: X-WT-Sync-Key header, compared in constant time against
 * the WT_SYNC_API_KEY constant defined in wp-config.php.
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class Sync_API {

	private const REST_NAMESPACE = 'wikitongues/v1';
	private const REST_ROUTE     = '/sync/(?P<post_type>[a-z_-]+)';

	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_sync' ),
				'permission_callback' => array( $this, 'check_auth' ),
				'args'                => array(
					'post_type' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Authenticate the request via the X-WT-Sync-Key header.
	 *
	 * Returns WP_Error (503) if the server constant is absent — this signals a
	 * misconfiguration rather than a bad client request.
	 * Returns WP_Error (401) if the key is wrong or missing.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return true|\WP_Error
	 */
	public function check_auth( \WP_REST_Request $request ): true|\WP_Error {
		if ( ! defined( 'WT_SYNC_API_KEY' ) || '' === WT_SYNC_API_KEY ) {
			return new \WP_Error(
				'wt_sync_not_configured',
				'WT_SYNC_API_KEY is not defined on this server.',
				array( 'status' => 503 )
			);
		}

		$provided = (string) $request->get_header( 'X-WT-Sync-Key' );

		if ( ! hash_equals( WT_SYNC_API_KEY, $provided ) ) {
			Logger::error( 'Rejected request: invalid X-WT-Sync-Key.' );

			return new \WP_Error(
				'wt_sync_unauthorized',
				'Invalid or missing X-WT-Sync-Key header.',
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Handle an authenticated sync request.
	 *
	 * post_type is validated here (after auth) so permission_callback always
	 * runs first — a bad key gets 401 before any business logic is checked.
	 *
	 * Dry-run mode: send X-WT-Dry-Run: 1 to resolve and preview all field
	 * writes without touching the database. Useful for validating Make.com
	 * payloads against staging or production before cutover.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_sync( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$post_type = $request->get_param( 'post_type' );
		$maps      = require WT_AIRTABLE_SYNC_DIR . 'config/field-maps.php';

		if ( ! isset( $maps[ $post_type ] ) ) {
			return new \WP_Error(
				'wt_sync_unsupported_post_type',
				sprintf( 'No field map defined for post_type "%s".', $post_type ),
				array( 'status' => 400 )
			);
		}

		$payload = $request->get_json_params();

		if ( empty( $payload ) ) {
			return new \WP_Error(
				'wt_sync_empty_payload',
				'Request body is empty or not valid JSON.',
				array( 'status' => 400 )
			);
		}

		$dry_run = '1' === (string) $request->get_header( 'X-WT-Dry-Run' );
		$result  = ( new Sync_Controller() )->sync( $post_type, $payload, $maps[ $post_type ], $dry_run );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( $result, 200 );
	}
}
