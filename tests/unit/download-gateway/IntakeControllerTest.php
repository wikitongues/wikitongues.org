<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\IntakeController;

class IntakeControllerTest extends TestCase {

	private IntakeController $controller;

	public function setUp(): void {
		parent::setUp();
		$this->controller = new IntakeController();

		WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'sanitize_text_field', array( 'return_arg' => 0 ) );
	}

	// -------------------------------------------------------------------------
	// Nonce / param validation
	// -------------------------------------------------------------------------

	public function test_submit_returns_403_for_invalid_nonce(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => false ) );

		$result = $this->controller->submit( 42, 7, 'bad-nonce', array() );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}

	public function test_submit_returns_400_for_invalid_post_id(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );

		$result = $this->controller->submit( 0, 7, 'valid-nonce', array() );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_submit_returns_400_for_invalid_person_id(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );

		$result = $this->controller->submit( 42, 0, 'valid-nonce', array() );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_submit_returns_404_when_post_not_found(): void {
		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => false ) );

		$result = $this->controller->submit( 42, 7, 'valid-nonce', array() );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	// -------------------------------------------------------------------------
	// DB error path
	// -------------------------------------------------------------------------

	public function test_submit_returns_500_when_db_insert_fails(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( false );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'document_files' ) );
		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-16 10:00:00' ) );
		WP_Mock::userFunction( 'wp_json_encode', array( 'return' => '{}' ) );

		$result = $this->controller->submit( 42, 7, 'valid-nonce', array() );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 500, $result->get_error_data()['status'] );
	}

	// -------------------------------------------------------------------------
	// Happy path
	// -------------------------------------------------------------------------

	public function test_submit_returns_true_on_success(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'wp_verify_nonce', array( 'return' => 1 ) );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'document_files' ) );
		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-16 10:00:00' ) );
		WP_Mock::userFunction(
			'wp_json_encode',
			array( 'return' => '{"use_case":"research"}' )
		);

		$result = $this->controller->submit(
			42,
			7,
			'valid-nonce',
			array( 'use_case' => 'research' )
		);

		$this->assertTrue( $result );
	}
}
