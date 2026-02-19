<?php
use WP_Mock\Tools\TestCase;

class SearchFilterTest extends TestCase {

	public function test_iso_code_sets_iso_and_standard_name_meta_query() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::userFunction(
			'get_query_var',
			array(
				'args'   => array( 's' ),
				'return' => 'rus',
			)
		);

		$query = new FakeQuery();
		searchfilter( $query );

		$meta_query = $query->calls['meta_query'];
		$keys       = array_column( array_filter( $meta_query, 'is_array' ), 'key' );
		$this->assertContains( 'iso_code', $keys );
		$this->assertContains( 'standard_name', $keys );
		$this->assertSame( 'OR', $meta_query['relation'] );
	}

	public function test_glottocode_sets_glottocode_meta_query_only() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::userFunction(
			'get_query_var',
			array(
				'args'   => array( 's' ),
				'return' => 'russ1263',
			)
		);

		$query = new FakeQuery();
		searchfilter( $query );

		$meta_query = $query->calls['meta_query'];
		$keys       = array_column( array_filter( $meta_query, 'is_array' ), 'key' );
		$this->assertContains( 'glottocode', $keys );
		$this->assertCount( 1, array_filter( $meta_query, 'is_array' ) );
	}

	public function test_generic_term_sets_broad_meta_query() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::userFunction(
			'get_query_var',
			array(
				'args'   => array( 's' ),
				'return' => 'Russian',
			)
		);

		$query = new FakeQuery();
		searchfilter( $query );

		$meta_query = $query->calls['meta_query'];
		$keys       = array_column( array_filter( $meta_query, 'is_array' ), 'key' );
		$this->assertContains( 'standard_name', $keys );
		$this->assertContains( 'alternate_names', $keys );
		$this->assertContains( 'nations_of_origin', $keys );
		$this->assertSame( 'OR', $meta_query['relation'] );
	}

	public function test_search_sets_post_type_and_clears_s() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );
		WP_Mock::userFunction(
			'get_query_var',
			array(
				'args'   => array( 's' ),
				'return' => 'Russian',
			)
		);

		$query = new FakeQuery();
		searchfilter( $query );

		$this->assertSame( array( 'languages', 'videos' ), $query->calls['post_type'] );
		$this->assertSame( '', $query->calls['s'] );
	}

	public function test_admin_request_returns_query_unmodified() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );

		$query  = new FakeQuery();
		$result = searchfilter( $query );

		$this->assertEmpty( $query->calls );
		$this->assertSame( $query, $result );
	}
}
