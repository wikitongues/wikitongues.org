<?php
/**
 * Http — the one place the exporter talks to the network.
 *
 * The clients take a transport callable, so tests replace the network with
 * canned responses. The default transport is WordPress's HTTP API.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * A minimal HTTP transport.
 *
 * @phpstan-type Response array{status: int, body: string}
 * @phpstan-type Transport callable(string, string, array<string, string>, string|null): Response
 */
final class Http {

	/**
	 * The default transport: wp_remote_request().
	 *
	 * @return callable(string, string, array<string, string>, string|null): array{status: int, body: string}
	 */
	public static function wordpress(): callable {
		return static function ( string $method, string $url, array $headers, ?string $body ): array {
			$response = wp_remote_request(
				$url,
				array(
					'method'  => $method,
					'headers' => $headers,
					'body'    => $body,
					'timeout' => 30,
				)
			);
			if ( is_wp_error( $response ) ) {
				throw new \RuntimeException( 'Request to ' . (string) wp_parse_url( $url, PHP_URL_HOST ) . ' failed: ' . $response->get_error_message() );
			}
			return array(
				'status' => (int) wp_remote_retrieve_response_code( $response ),
				'body'   => (string) wp_remote_retrieve_body( $response ),
			);
		};
	}

	/**
	 * A JSON body, or an exception naming the service and status.
	 *
	 * @param array{status: int, body: string} $response A response.
	 * @param string                           $service  For the message, e.g. "Google Docs".
	 * @return array<string, mixed>
	 */
	public static function json( array $response, string $service ): array {
		$data = json_decode( $response['body'], true );
		if ( $response['status'] < 200 || $response['status'] >= 300 ) {
			$detail = is_array( $data ) ? (string) ( $data['error']['message'] ?? $data['error']['type'] ?? $data['error'] ?? '' ) : '';
			throw new \RuntimeException( $service . ' returned HTTP ' . $response['status'] . ( '' !== $detail ? ': ' . $detail : '' ) );
		}
		if ( ! is_array( $data ) ) {
			throw new \RuntimeException( $service . ' returned something that is not JSON.' );
		}
		return $data;
	}
}
