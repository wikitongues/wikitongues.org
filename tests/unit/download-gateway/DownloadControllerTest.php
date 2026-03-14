<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\DownloadController;
use WT\DownloadGateway\DocumentFileResolver;
use WT\DownloadGateway\FileResolverRegistry;

class DownloadControllerTest extends TestCase {

	private DownloadController $controller;

	public function setUp(): void {
		parent::setUp();
		FileResolverRegistry::reset();
		$this->controller = new DownloadController();

		// Sanitization functions used by DownloadEventRepository::log().
		WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'sanitize_text_field', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'esc_url_raw', array( 'return_arg' => 0 ) );
	}

	// -------------------------------------------------------------------------
	// resolve() — ID dispatch
	// -------------------------------------------------------------------------

	public function test_resolve_returns_400_for_malformed_id(): void {
		$result = $this->controller->resolve( 'not-valid!!' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_resolve_returns_400_for_partial_hex_token(): void {
		// 63 chars — one short of a valid token.
		$result = $this->controller->resolve( str_repeat( 'a', 63 ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	// -------------------------------------------------------------------------
	// resolve() — post ID path
	// -------------------------------------------------------------------------

	public function test_resolve_returns_404_when_no_resolver_for_post_type(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );

		$result = $this->controller->resolve( '42' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_resolve_returns_403_when_policy_is_hard(): void {
		FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );

		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'document_files' ) );
		// PolicyResolver tier 1: per-resource meta returns 'hard'.
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => 'hard' ) );

		$result = $this->controller->resolve( '42' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}

	public function test_resolve_returns_file_url_for_valid_post_id(): void {
		FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );

		$this->mock_policy_none();
		$this->mock_wpdb_inserts( 3 ); // token + click event + redirect event
		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-14 12:00:00' ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'document_files' ) );
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file', 42 ),
				'return' => 'https://example.com/uploads/doc.pdf',
			)
		);
		WP_Mock::userFunction( 'do_action', array( 'return' => null ) );

		$result = $this->controller->resolve( '42' );
		$this->assertSame( 'https://example.com/uploads/doc.pdf', $result );
	}

	public function test_resolve_returns_404_when_resolver_returns_null_url(): void {
		FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );

		$this->mock_policy_none();
		$this->mock_wpdb_inserts( 2 ); // token + click event; redirect not reached (resolver returns null)
		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-14 12:00:00' ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'document_files' ) );
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file', 42 ),
				'return' => false, // ACF field empty
			)
		);
		WP_Mock::userFunction( 'do_action', array( 'return' => null ) );

		$result = $this->controller->resolve( '42' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	// -------------------------------------------------------------------------
	// resolve() — token path
	// -------------------------------------------------------------------------

	public function test_resolve_returns_410_when_token_not_found(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( null );
		$GLOBALS['wpdb'] = $wpdb;

		$result = $this->controller->resolve( str_repeat( 'a', 64 ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 410, $result->get_error_data()['status'] );
	}

	public function test_resolve_returns_410_when_token_is_expired(): void {
		$row             = new stdClass();
		$row->used_at    = null;
		$row->expires_at = '2000-01-01 00:00:00'; // expired
		$row->post_id    = 42;
		$row->visitor_id = null;

		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( $row );
		$GLOBALS['wpdb'] = $wpdb;

		$result = $this->controller->resolve( str_repeat( 'a', 64 ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 410, $result->get_error_data()['status'] );
	}

	public function test_resolve_returns_410_when_token_already_used(): void {
		$row             = new stdClass();
		$row->used_at    = '2026-03-14 10:00:00'; // already redeemed
		$row->expires_at = '2099-01-01 00:00:00';
		$row->post_id    = 42;
		$row->visitor_id = null;

		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( $row );
		$GLOBALS['wpdb'] = $wpdb;

		$result = $this->controller->resolve( str_repeat( 'a', 64 ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 410, $result->get_error_data()['status'] );
	}

	public function test_resolve_returns_file_url_for_valid_token(): void {
		FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );

		$row             = new stdClass();
		$row->used_at    = null;
		$row->expires_at = '2099-01-01 00:00:00';
		$row->post_id    = 42;
		$row->visitor_id = str_repeat( 'b', 32 );

		$wpdb             = Mockery::mock( 'wpdb' );
		$wpdb->prefix     = 'wp_';
		$wpdb->insert_id  = 1;
		$wpdb->last_error = '';
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'SQL' );
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( $row );
		$wpdb->shouldReceive( 'update' )->once()->andReturn( 1 );   // mark_used
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( 1 );   // redirect event
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-14 12:00:00' ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'document_files' ) );
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file', 42 ),
				'return' => 'https://example.com/uploads/doc.pdf',
			)
		);
		WP_Mock::userFunction( 'do_action', array( 'return' => null ) );

		$result = $this->controller->resolve( str_repeat( 'a', 64 ) );
		$this->assertSame( 'https://example.com/uploads/doc.pdf', $result );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function mock_policy_none(): void {
		// PolicyResolver tier 1: per-resource meta — empty, fall through.
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => '' ) );
		// PolicyResolver tier 2: taxonomy terms — none, fall through.
		WP_Mock::userFunction( 'get_object_taxonomies', array( 'return' => array() ) );
		// PolicyResolver tier 3: global option — 'none'.
		WP_Mock::userFunction( 'get_option', array( 'return' => 'none' ) );
	}

	private function mock_wpdb_inserts( int $times ): void {
		$wpdb             = Mockery::mock( 'wpdb' );
		$wpdb->prefix     = 'wp_';
		$wpdb->last_error = '';
		$wpdb->insert_id  = 1;
		$wpdb->shouldReceive( 'insert' )->times( $times )->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;
	}
}
