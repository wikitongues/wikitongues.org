<?php
/**
 * Server-side proxy for the typeahead's Airtable lookups.
 *
 * The browser calls GET /wp-json/wt/v1/typeahead?q=…; this class runs the
 * Airtable query with the token from the Custom Search options page, so the
 * token never reaches the browser. Results match what the app used to compute
 * client-side. See plan.md 7.9.
 *
 * @package typeahead
 */

class WT_Typeahead_Proxy {

	const REST_NAMESPACE = 'wt/v1';
	const REST_ROUTE     = '/typeahead';
	const API_URL        = 'https://api.airtable.com/v0';
	const CACHE_TTL      = 600; // 10 minutes.
	const MIN_LENGTH     = 2;
	const MAX_LENGTH     = 100;
	const TIMEOUT        = 5;

	/**
	 * Hooks the REST route.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	/**
	 * Registers GET wt/v1/typeahead.
	 */
	public static function register_route() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * REST callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function handle( $request ) {
		return rest_ensure_response( self::search( (string) $request->get_param( 'q' ) ) );
	}

	/**
	 * Reads the Custom Search options (ACF options page "Global: Search").
	 *
	 * @return array{api_key:string,base_id:string,table:string,label_field_id:string,label_field_name:string,identifier_field:string}
	 */
	public static function config() {
		return array(
			'api_key'          => (string) get_option( 'options_airtable_api_key', '' ),
			'base_id'          => (string) get_option( 'options_airtable_base_id', '' ),
			'table'            => (string) get_option( 'options_airtable_table_name', '' ),
			'label_field_id'   => (string) get_option( 'options_airtable_label_field_id', '' ),
			'label_field_name' => (string) get_option( 'options_airtable_label_field_name', '' ),
			'identifier_field' => (string) get_option( 'options_airtable_identifier_field', '' ),
		);
	}

	/**
	 * Trims and lowercases a query.
	 *
	 * @param string $query Raw query.
	 * @return string
	 */
	public static function normalize_query( $query ) {
		return mb_strtolower( trim( $query ) );
	}

	/**
	 * Whether a normalized query should reach Airtable.
	 *
	 * The empty query is allowed: the app asks for it once to pick placeholder
	 * examples. A single character is not, and very long input is refused.
	 *
	 * @param string $query Normalized query.
	 * @return bool
	 */
	public static function is_searchable( $query ) {
		$length = mb_strlen( $query );
		return 0 === $length || ( $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH );
	}

	/**
	 * Escapes a value for use inside a double-quoted Airtable formula string.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function escape_formula_string( $value ) {
		return str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $value );
	}

	/**
	 * Builds the filterByFormula the app used, with the query escaped.
	 *
	 * @param string $query            Normalized query.
	 * @param string $label_field_id   Label field.
	 * @param string $identifier_field Identifier field.
	 * @return string
	 */
	public static function build_formula( $query, $label_field_id, $identifier_field ) {
		$q = self::escape_formula_string( $query );
		return sprintf(
			'OR(FIND(LOWER("%1$s"), LOWER({%2$s})), FIND(LOWER("%1$s"), LOWER({%3$s})))',
			$q,
			$label_field_id,
			$identifier_field
		);
	}

	/**
	 * Builds the Airtable list-records URL.
	 *
	 * @param string $query  Normalized query.
	 * @param array  $config Config from config().
	 * @return string
	 */
	public static function build_url( $query, array $config ) {
		$params = array(
			'filterByFormula=' . rawurlencode( self::build_formula( $query, $config['label_field_id'], $config['identifier_field'] ) ),
		);
		$fields = array( $config['label_field_id'], $config['label_field_name'], $config['identifier_field'], 'Relation [Oral Histories]' );
		foreach ( $fields as $field ) {
			$params[] = rawurlencode( 'fields[]' ) . '=' . rawurlencode( $field );
		}
		return sprintf(
			'%s/%s/%s?%s',
			self::API_URL,
			rawurlencode( $config['base_id'] ),
			rawurlencode( $config['table'] ),
			implode( '&', $params )
		);
	}

	/**
	 * Flattens an Airtable cell (string, number or list) to a string.
	 *
	 * @param mixed $value Cell value.
	 * @return string
	 */
	private static function cell_text( $value ) {
		if ( is_array( $value ) ) {
			return implode( ',', array_map( array( __CLASS__, 'cell_text' ), $value ) );
		}
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Turns Airtable records into suggestions, as the app did client-side:
	 * keep records where one comma-separated label contains the query, map to
	 * {id, label, identifier, resourced}, sort by label.
	 *
	 * @param array  $records Airtable records.
	 * @param string $query   Normalized query.
	 * @param array  $config  Config from config().
	 * @return array<int, array{id:string,label:string,identifier:string,resourced:mixed}>
	 */
	public static function build_suggestions( array $records, $query, array $config ) {
		$suggestions = array();
		foreach ( $records as $record ) {
			$fields     = isset( $record['fields'] ) && is_array( $record['fields'] ) ? $record['fields'] : array();
			$by_id      = self::cell_text( $fields[ $config['label_field_id'] ] ?? '' );
			$by_name    = self::cell_text( $fields[ $config['label_field_name'] ] ?? '' );
			$identifier = self::cell_text( $fields[ $config['identifier_field'] ] ?? '' );

			$labels = array_filter(
				array( $by_id, $by_name, $identifier ),
				static function ( $label ) {
					return '' !== $label;
				}
			);
			if ( ! $labels ) {
				continue;
			}

			$match = false;
			foreach ( explode( ',', implode( ', ', $labels ) ) as $label ) {
				if ( str_contains( mb_strtolower( trim( $label ) ), $query ) ) {
					$match = true;
					break;
				}
			}
			if ( ! $match ) {
				continue;
			}

			$suggestions[] = array(
				'id'         => (string) ( $record['id'] ?? '' ),
				'label'      => '' !== $by_id ? $by_id : $by_name,
				'identifier' => $identifier,
				'resourced'  => $fields['Relation [Oral Histories]'] ?? null,
			);
		}

		usort(
			$suggestions,
			static function ( $a, $b ) {
				return strcasecmp( $a['label'], $b['label'] );
			}
		);

		return $suggestions;
	}

	/**
	 * Runs a search: validates, serves from cache, else asks Airtable.
	 *
	 * @param string $raw_query Query as typed.
	 * @return array Suggestions; [] on any failure.
	 */
	public static function search( $raw_query ) {
		$query = self::normalize_query( $raw_query );
		if ( ! self::is_searchable( $query ) ) {
			return array();
		}

		$config = self::config();
		if ( '' === $config['api_key'] || '' === $config['base_id'] || '' === $config['table'] ) {
			error_log( 'wt-typeahead: Custom Search settings are incomplete.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return array();
		}

		$cache_key = 'wt_typeahead_' . md5( $query . '|' . $config['base_id'] . '|' . $config['table'] );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			self::build_url( $query, $config ),
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array( 'Authorization' => 'Bearer ' . $config['api_key'] ),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'wt-typeahead: Airtable request failed: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return array();
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			error_log( 'wt-typeahead: Airtable returned HTTP ' . $code ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return array();
		}

		$body    = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$records = is_array( $body ) && isset( $body['records'] ) && is_array( $body['records'] ) ? $body['records'] : array();

		$suggestions = self::build_suggestions( $records, $query, $config );
		set_transient( $cache_key, $suggestions, self::CACHE_TTL );

		return $suggestions;
	}
}
