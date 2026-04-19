<?php
/**
 * IntakeController — handles intake form submissions (modal step 2).
 *
 * Registers POST /wp-json/gateway/v1/intake. The testable logic lives in
 * submit() — same pattern as GateController.
 *
 * Intake is supplementary: a failed save does not block the download. The
 * endpoint returns a success response regardless so the JS always proceeds
 * to the download redirect.
 *
 * Field definitions are registered via the `gateway_intake_fields` PHP filter
 * in theme or CPT-specific code. The controller stores whatever key-value
 * responses the JS sends — it does not validate field shape, only sanitizes.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class IntakeController {

	public function register_routes(): void {
		register_rest_route(
			GATEWAY_REST_NAMESPACE,
			'/intake',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST callback — not unit tested.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$responses = $request->get_param( 'responses' );
		if ( ! is_array( $responses ) ) {
			$responses = array();
		}

		$result = $this->submit(
			(int) ( $request->get_param( 'post_id' ) ?? 0 ),
			(string) ( $request->get_param( 'person_cookie' ) ?? '' ),
			(string) ( $request->get_param( 'nonce' ) ?? '' ),
			$responses
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Process an intake submission. Returns true on success, WP_Error on failure.
	 *
	 * @param int                  $post_id       Post ID of the downloaded resource.
	 * @param string               $person_cookie HMAC-signed cookie value from gateway_gated.
	 * @param string               $nonce         WP nonce value.
	 * @param array<string,string> $responses     Key-value map of field responses.
	 * @return true|\WP_Error
	 */
	public function submit( int $post_id, string $person_cookie, string $nonce, array $responses ): bool|\WP_Error {
		if ( ! wp_verify_nonce( $nonce, 'gateway_gate' ) ) {
			return new \WP_Error( 'invalid_nonce', 'Request could not be verified.', array( 'status' => 403 ) );
		}

		$person_id = PersonCookie::verify( $person_cookie );
		if ( $post_id <= 0 || false === $person_id ) {
			return new \WP_Error( 'invalid_params', 'Invalid parameters.', array( 'status' => 400 ) );
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return new \WP_Error( 'invalid_post', 'Post not found.', array( 'status' => 404 ) );
		}

		// Sanitize all response values — keys and scalar strings only.
		$sanitized = array();
		foreach ( $responses as $key => $value ) {
			$sanitized[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
		}

		$intake_id = IntakeRepository::save( $person_id, $post_id, $post_type, $sanitized );
		if ( false === $intake_id ) {
			return new \WP_Error( 'db_error', 'Could not save intake response.', array( 'status' => 500 ) );
		}

		// Enqueue intake webhook if an endpoint is configured.
		$endpoint = SettingsRepository::get_webhook_endpoint();
		if ( '' !== $endpoint && filter_var( $endpoint, FILTER_VALIDATE_URL ) ) {
			$intake_set = IntakeResolver::resolve( $post_id )['set'];
			WebhookDispatcher::enqueue(
				(int) $intake_id,
				$endpoint,
				array(
					'type'               => 'intake',
					'person_id'          => $person_id,
					'download_event_id'  => DownloadEventRepository::find_redirect_id( $person_id, $post_id ),
					'post_id'            => $post_id,
					'post_type'          => $post_type,
					'airtable_record_id' => get_post_meta( $post_id, '_airtable_record_id', true ) ?: null,
					'intake_set'         => $intake_set,
					'responses'          => $sanitized,
					'created_at'         => current_time( 'mysql' ),
				)
			);
		}

		return true;
	}
}
