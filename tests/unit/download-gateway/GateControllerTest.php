<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\GateController;
use WT\DownloadGateway\PeopleRepository;

class GateControllerTest extends TestCase {

	private GateController $controller;

	public function setUp(): void {
		parent::setUp();
		$this->controller = new GateController();

		WP_Mock::userFunction( 'sanitize_text_field', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'sanitize_email', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'sanitize_text_field', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'esc_url_raw', array( 'return_arg' => 0 ) );
	}

	// -------------------------------------------------------------------------
	// Spam / abuse prevention
	// -------------------------------------------------------------------------

	public function test_submit_returns_silent_success_when_honeypot_filled(): void {
		$result = $this->controller->submit( 42, 'user@example.com', 'Jane', true, 'nonce', 'bot-value' );
		$this->assertIsArray( $result );
		$this->assertNull( $result['token'] );
	}

	public function test_submit_returns_403_for_invalid_nonce(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => false ) );

		$result = $this->controller->submit( 42, 'user@example.com', 'Jane', true, 'bad-nonce', '' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}

	public function test_submit_returns_429_when_rate_limit_exceeded(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_transient', array( 'return' => 10 ) ); // at limit

		$result = $this->controller->submit( 42, 'user@example.com', 'Jane', true, 'valid-nonce', '', array(), array( 'REMOTE_ADDR' => '10.0.0.1' ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 429, $result->get_error_data()['status'] );
	}

	// -------------------------------------------------------------------------
	// Field validation
	// -------------------------------------------------------------------------

	public function test_submit_returns_400_for_invalid_post_id(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_transient', array( 'return' => 0 ) );
		WP_Mock::userFunction( 'set_transient', array( 'return' => true ) );

		$result = $this->controller->submit( 0, 'user@example.com', 'Jane', true, 'valid-nonce', '', array(), array( 'REMOTE_ADDR' => '10.0.0.1' ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_submit_returns_400_for_invalid_email(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_transient', array( 'return' => 0 ) );
		WP_Mock::userFunction( 'set_transient', array( 'return' => true ) );
		WP_Mock::userFunction( 'is_email', array( 'return' => false ) );

		$result = $this->controller->submit( 42, 'not-an-email', 'Jane', true, 'valid-nonce', '', array(), array( 'REMOTE_ADDR' => '10.0.0.1' ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 'invalid_email', $result->get_error_code() );
	}

	public function test_submit_returns_400_for_empty_name(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_transient', array( 'return' => 0 ) );
		WP_Mock::userFunction( 'set_transient', array( 'return' => true ) );
		WP_Mock::userFunction( 'is_email', array( 'return' => true ) );

		$result = $this->controller->submit( 42, 'user@example.com', '   ', true, 'valid-nonce', '', array(), array( 'REMOTE_ADDR' => '10.0.0.1' ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 'invalid_name', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// Happy path
	// -------------------------------------------------------------------------

	public function test_submit_returns_token_on_success(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb             = Mockery::mock( 'wpdb' );
		$wpdb->prefix     = 'wp_';
		$wpdb->insert_id  = 1;
		$wpdb->last_error = '';
		// people upsert: prepare (SELECT) + get_var (null = new) + insert
		// token create: insert
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( null );
		$wpdb->shouldReceive( 'insert' )->twice()->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_transient', array( 'return' => 0 ) );
		WP_Mock::userFunction( 'set_transient', array( 'return' => true ) );
		WP_Mock::userFunction( 'is_email', array( 'return' => true ) );
		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-15 10:00:00' ) );

		$result = $this->controller->submit(
			42,
			'user@example.com',
			'Jane Doe',
			true,
			'valid-nonce',
			'',
			array(),
			array( 'REMOTE_ADDR' => '10.0.0.1' )
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'token', $result );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $result['token'] );
	}

	public function test_submit_returns_500_on_db_error(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( null );
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( false ); // people insert fails
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_transient', array( 'return' => 0 ) );
		WP_Mock::userFunction( 'set_transient', array( 'return' => true ) );
		WP_Mock::userFunction( 'is_email', array( 'return' => true ) );
		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-15 10:00:00' ) );

		$result = $this->controller->submit(
			42,
			'user@example.com',
			'Jane Doe',
			true,
			'valid-nonce',
			'',
			array(),
			array( 'REMOTE_ADDR' => '10.0.0.1' )
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 500, $result->get_error_data()['status'] );
	}
}
