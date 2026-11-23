<?php
use WP_Mock\Tools\TestCase;

/**
 * Plan 7.9 example cases for the typeahead's Airtable proxy.
 */
class TypeaheadProxyTest extends TestCase {

	private const OPTIONS = array(
		'options_airtable_api_key'          => 'test-token',
		'options_airtable_base_id'          => 'appTEST',
		'options_airtable_table_name'       => 'Languages',
		'options_airtable_label_field_id'   => 'fldLabel',
		'options_airtable_label_field_name' => 'Language Names',
		'options_airtable_identifier_field' => 'Identifier',
	);

	private function config(): array {
		return array(
			'api_key'          => 'test-token',
			'base_id'          => 'appTEST',
			'table'            => 'Languages',
			'label_field_id'   => 'fldLabel',
			'label_field_name' => 'Language Names',
			'identifier_field' => 'Identifier',
		);
	}

	private function records(): array {
		return array(
			array(
				'id'     => 'recHaw',
				'fields' => array(
					'Language Names'            => 'Hawaiian, ʻŌlelo Hawaiʻi',
					'Identifier'                => 'haw',
					'Relation [Oral Histories]' => array( 'recOH1' ),
				),
			),
			array(
				'id'     => 'recTah',
				'fields' => array(
					'Language Names' => 'Tahitian, Reo Tahiti',
					'Identifier'     => 'tah',
				),
			),
			array(
				'id'     => 'recCha',
				'fields' => array(
					'Language Names' => 'Chamorro, Chamoru',
					'Identifier'     => 'cha',
				),
			),
			array(
				'id'     => 'recEmpty',
				'fields' => array(),
			),
		);
	}

	private function mock_options( array $overrides = array() ): void {
		$options = array_merge( self::OPTIONS, $overrides );
		WP_Mock::userFunction( 'get_option' )->andReturnUsing(
			static function ( $name, $fallback = false ) use ( $options ) {
				return $options[ $name ] ?? $fallback;
			}
		);
	}

	private function mock_airtable( array $records, int $code = 200, int $times = 1 ): void {
		WP_Mock::userFunction( 'wp_remote_get' )->times( $times )->andReturn( array( 'response' => array( 'code' => $code ) ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( $code );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( wp_json_encode_stub( array( 'records' => $records ) ) );
	}

	public function test_haw_finds_hawaiian_with_resources_flag() {
		$result = WT_Typeahead_Proxy::build_suggestions( $this->records(), 'haw', $this->config() );
		$this->assertSame( array( 'recHaw' ), array_column( $result, 'id' ) );
		$this->assertSame( 'Hawaiian, ʻŌlelo Hawaiʻi', $result[0]['label'] );
		$this->assertSame( 'haw', $result[0]['identifier'] );
		$this->assertSame( array( 'recOH1' ), $result[0]['resourced'] );
	}

	public function test_tahitian_and_chamorro_match_by_name() {
		$this->assertSame( array( 'recTah' ), array_column( WT_Typeahead_Proxy::build_suggestions( $this->records(), 'tahitian', $this->config() ), 'id' ) );
		$this->assertContains( 'recCha', array_column( WT_Typeahead_Proxy::build_suggestions( $this->records(), 'cha', $this->config() ), 'id' ) );
	}

	public function test_results_sorted_by_label_and_unlabelled_records_dropped() {
		$result = WT_Typeahead_Proxy::build_suggestions( $this->records(), '', $this->config() );
		$this->assertSame( array( 'recCha', 'recHaw', 'recTah' ), array_column( $result, 'id' ) );
	}

	public function test_query_is_escaped_in_formula() {
		$formula = WT_Typeahead_Proxy::build_formula( 'a"b\\', 'fldLabel', 'Identifier' );
		$this->assertStringContainsString( 'LOWER("a\\"b\\\\")', $formula );
		$this->assertStringNotContainsString( '"a"b', $formula );
	}

	public function test_url_carries_formula_and_fields() {
		$url = WT_Typeahead_Proxy::build_url( 'haw', $this->config() );
		$this->assertStringStartsWith( 'https://api.airtable.com/v0/appTEST/Languages?filterByFormula=', $url );
		$this->assertStringContainsString( rawurlencode( 'fields[]' ) . '=' . rawurlencode( 'Relation [Oral Histories]' ), $url );
	}

	public function test_single_character_returns_empty_without_calling_airtable() {
		WP_Mock::userFunction( 'wp_remote_get' )->never();
		$this->assertSame( array(), WT_Typeahead_Proxy::search( 'h' ) );
		$this->assertSame( array(), WT_Typeahead_Proxy::search( str_repeat( 'a', 101 ) ) );
	}

	public function test_search_calls_airtable_once_then_serves_cache() {
		$this->mock_options();
		$this->mock_airtable( $this->records() );
		$cache = new ArrayObject();
		WP_Mock::userFunction( 'get_transient' )->andReturnUsing(
			static function ( $key ) use ( $cache ) {
				return $cache[ $key ] ?? false;
			}
		);
		WP_Mock::userFunction( 'set_transient' )->once()->andReturnUsing(
			static function ( $key, $value ) use ( $cache ) {
				$cache[ $key ] = $value;
				return true;
			}
		);

		$first  = WT_Typeahead_Proxy::search( ' HAW ' );
		$second = WT_Typeahead_Proxy::search( 'haw' );

		$this->assertSame( array( 'recHaw' ), array_column( $first, 'id' ) );
		$this->assertSame( $first, $second );
	}

	public function test_airtable_error_returns_empty_and_is_not_cached() {
		$this->mock_options();
		$this->mock_airtable( array(), 401 );
		WP_Mock::userFunction( 'get_transient' )->andReturn( false );
		WP_Mock::userFunction( 'set_transient' )->never();
		$this->assertSame( array(), WT_Typeahead_Proxy::search( 'haw' ) );
	}

	public function test_missing_token_returns_empty_without_calling_airtable() {
		$this->mock_options( array( 'options_airtable_api_key' => '' ) );
		WP_Mock::userFunction( 'wp_remote_get' )->never();
		$this->assertSame( array(), WT_Typeahead_Proxy::search( 'haw' ) );
	}
}

/**
 * JSON-encodes a test response body.
 *
 * @param array $data Data.
 * @return string
 */
function wp_json_encode_stub( array $data ): string {
	return (string) json_encode( $data );
}
