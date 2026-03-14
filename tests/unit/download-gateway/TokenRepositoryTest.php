<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\TokenRepository;

class TokenRepositoryTest extends TestCase {

	// -------------------------------------------------------------------------
	// is_valid() — pure function, no mocking required
	// -------------------------------------------------------------------------

	public function test_is_valid_returns_true_when_unused_and_not_expired(): void {
		$row             = new stdClass();
		$row->used_at    = null;
		$row->expires_at = '2099-01-01 00:00:00';

		$this->assertTrue( TokenRepository::is_valid( $row ) );
	}

	public function test_is_valid_returns_false_when_already_used(): void {
		$row             = new stdClass();
		$row->used_at    = '2026-03-14 10:00:00';
		$row->expires_at = '2099-01-01 00:00:00';

		$this->assertFalse( TokenRepository::is_valid( $row ) );
	}

	public function test_is_valid_returns_false_when_expired(): void {
		$row             = new stdClass();
		$row->used_at    = null;
		$row->expires_at = '2000-01-01 00:00:00';

		$this->assertFalse( TokenRepository::is_valid( $row ) );
	}

	public function test_is_valid_returns_false_when_used_and_expired(): void {
		$row             = new stdClass();
		$row->used_at    = '2000-01-01 09:00:00';
		$row->expires_at = '2000-01-01 10:00:00';

		$this->assertFalse( TokenRepository::is_valid( $row ) );
	}

	public function test_is_valid_accepts_explicit_now_timestamp(): void {
		$row             = new stdClass();
		$row->used_at    = null;
		$row->expires_at = '2026-03-14 12:00:00'; // expires at noon

		// One second before expiry — valid.
		$this->assertTrue( TokenRepository::is_valid( $row, strtotime( '2026-03-14 11:59:59' ) ) );

		// Exactly at expiry — invalid (not strictly greater than).
		$this->assertFalse( TokenRepository::is_valid( $row, strtotime( '2026-03-14 12:00:00' ) ) );

		// One second after expiry — invalid.
		$this->assertFalse( TokenRepository::is_valid( $row, strtotime( '2026-03-14 12:00:01' ) ) );
	}

	// -------------------------------------------------------------------------
	// create() — requires wpdb mock
	// -------------------------------------------------------------------------

	public function test_create_returns_64_char_hex_token(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-14 12:00:00' ) );

		$token = TokenRepository::create( 42 );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $token );
	}

	public function test_create_inserts_into_correct_table(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_gateway_tokens',
				Mockery::on( fn( $data ) => $data['post_id'] === 99 && null === $data['person_id'] )
			)
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-14 12:00:00' ) );

		$token = TokenRepository::create( 99 );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $token );
	}

	// -------------------------------------------------------------------------
	// find_by_token() — requires wpdb mock
	// -------------------------------------------------------------------------

	public function test_find_by_token_returns_null_when_not_found(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( null );
		$GLOBALS['wpdb'] = $wpdb;

		$this->assertNull( TokenRepository::find_by_token( 'nosuchtoken' ) );
	}

	public function test_find_by_token_returns_row_when_found(): void {
		$row             = new stdClass();
		$row->token      = 'abc123';
		$row->used_at    = null;
		$row->expires_at = '2099-01-01 00:00:00';

		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( $row );
		$GLOBALS['wpdb'] = $wpdb;

		$result = TokenRepository::find_by_token( 'abc123' );

		$this->assertSame( $row, $result );
	}

	// -------------------------------------------------------------------------
	// mark_used() — requires wpdb mock
	// -------------------------------------------------------------------------

	public function test_mark_used_returns_true_on_success(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'update' )->once()->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-14 12:00:00' ) );

		$this->assertTrue( TokenRepository::mark_used( 'sometoken' ) );
	}

	public function test_mark_used_returns_false_on_failure(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'update' )->once()->andReturn( false );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-14 12:00:00' ) );

		$this->assertFalse( TokenRepository::mark_used( 'sometoken' ) );
	}

	// -------------------------------------------------------------------------
	// purge_expired() — requires wpdb mock
	// -------------------------------------------------------------------------

	public function test_purge_expired_returns_row_count(): void {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'DELETE_SQL' );
		$wpdb->shouldReceive( 'query' )->once()->andReturn( 5 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-14 12:00:00' ) );

		$this->assertSame( 5, TokenRepository::purge_expired() );
	}
}
