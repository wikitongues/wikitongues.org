<?php
/**
 * Airtable — reads the Language Rights base.
 *
 * The token comes from WT_RIGHTS_AIRTABLE_TOKEN, filled by `op run`. Records
 * are fetched with returnFieldsByFieldId, so the exporter keys on field IDs and
 * a renamed column changes nothing. Select values are read by name: in this
 * base, option IDs are reused across fields ("no" and "neutral" share one).
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * A read-only Airtable client.
 */
final class Airtable {

	private const API = 'https://api.airtable.com/v0/';

	/**
	 * The token.
	 *
	 * @var string
	 */
	private string $token;

	/**
	 * The transport.
	 *
	 * @var callable(string, string, array<string, string>, string|null): array{status: int, body: string}
	 */
	private $http;

	/**
	 * Builds a client.
	 *
	 * @param string                                                                                $token A personal access token.
	 * @param callable(string, string, array<string, string>, string|null): array{status: int, body: string} $http  The transport.
	 */
	public function __construct( string $token, callable $http ) {
		$this->token = $token;
		$this->http  = $http;
	}

	/**
	 * A client from the environment, or an exception explaining how to run it.
	 *
	 * @param callable(string, string, array<string, string>, string|null): array{status: int, body: string} $http The transport.
	 */
	public static function from_env( callable $http ): self {
		$token = (string) getenv( 'WT_RIGHTS_AIRTABLE_TOKEN' );
		if ( '' === $token ) {
			throw new \RuntimeException( 'WT_RIGHTS_AIRTABLE_TOKEN is not set. Run through 1Password: op run --env-file ~/.config/wikitongues/rights.env -- wp …' );
		}
		return new self( $token, $http );
	}

	/**
	 * Every record in a table, following pagination. Fields are keyed by field ID.
	 *
	 * @param string       $base   Base ID.
	 * @param string       $table  Table ID.
	 * @param list<string> $fields Field IDs to return; empty for all.
	 * @return list<array{id: string, fields: array<string, mixed>}>
	 */
	public function records( string $base, string $table, array $fields = array() ): array {
		$records = array();
		$offset  = '';
		do {
			$query = array(
				'returnFieldsByFieldId' => 'true',
				'pageSize'              => '100',
			);
			if ( '' !== $offset ) {
				$query['offset'] = $offset;
			}
			$url = self::API . rawurlencode( $base ) . '/' . rawurlencode( $table ) . '?' . http_build_query( $query );
			foreach ( $fields as $field ) {
				$url .= '&' . rawurlencode( 'fields[]' ) . '=' . rawurlencode( $field );
			}
			$page = Http::json( ( $this->http )( 'GET', $url, array( 'Authorization' => 'Bearer ' . $this->token ), null ), 'Airtable' );
			foreach ( $page['records'] ?? array() as $record ) {
				$records[] = array(
					'id'     => (string) $record['id'],
					'fields' => (array) ( $record['fields'] ?? array() ),
				);
			}
			$offset = (string) ( $page['offset'] ?? '' );
		} while ( '' !== $offset );
		return $records;
	}
}
