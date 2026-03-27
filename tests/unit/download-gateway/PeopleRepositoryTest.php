<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\PeopleRepository;

class PeopleRepositoryTest extends TestCase {

	// -------------------------------------------------------------------------
	// hash_email() — pure function, no mocking required
	// -------------------------------------------------------------------------

	public function test_hash_email_returns_64_char_hex(): void {
		$hash = PeopleRepository::hash_email( 'user@example.com' );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $hash );
	}

	public function test_hash_email_is_case_insensitive(): void {
		$this->assertSame(
			PeopleRepository::hash_email( 'User@Example.COM' ),
			PeopleRepository::hash_email( 'user@example.com' )
		);
	}

	public function test_hash_email_trims_whitespace(): void {
		$this->assertSame(
			PeopleRepository::hash_email( '  user@example.com  ' ),
			PeopleRepository::hash_email( 'user@example.com' )
		);
	}

	public function test_different_emails_produce_different_hashes(): void {
		$this->assertNotSame(
			PeopleRepository::hash_email( 'a@example.com' ),
			PeopleRepository::hash_email( 'b@example.com' )
		);
	}

	// -------------------------------------------------------------------------
	// upsert() — insert path
	// -------------------------------------------------------------------------

	public function test_upsert_inserts_new_person_and_returns_id(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb            = Mockery::mock( 'wpdb' );
		$wpdb->prefix    = 'wp_';
		$wpdb->insert_id = 7;
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( null );
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-15 10:00:00' ) );
		WP_Mock::userFunction( 'sanitize_text_field', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'sanitize_email', array( 'return_arg' => 0 ) );

		$result = PeopleRepository::upsert( 'user@example.com', 'Jane Doe', true );

		$this->assertSame( 7, $result );
	}

	public function test_upsert_returns_false_on_db_error(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( null );
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( false );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-15 10:00:00' ) );
		WP_Mock::userFunction( 'sanitize_text_field', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'sanitize_email', array( 'return_arg' => 0 ) );

		$result = PeopleRepository::upsert( 'user@example.com', 'Jane Doe', true );

		$this->assertFalse( $result );
	}

	// -------------------------------------------------------------------------
	// upsert() — update path
	// -------------------------------------------------------------------------

	public function test_upsert_updates_existing_person_and_returns_existing_id(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( '42' );
		$wpdb->shouldReceive( 'update' )->once()->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'sanitize_text_field', array( 'return_arg' => 0 ) );

		$result = PeopleRepository::upsert( 'user@example.com', 'Jane Doe', true );

		$this->assertSame( 42, $result );
	}

	// -------------------------------------------------------------------------
	// find_by_id()
	// -------------------------------------------------------------------------

	public function test_find_by_id_returns_row_when_found(): void {
		$row       = new stdClass();
		$row->id   = 5;
		$row->name = 'Jane Doe';

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( $row );
		$GLOBALS['wpdb'] = $wpdb;

		$this->assertSame( $row, PeopleRepository::find_by_id( 5 ) );
	}

	public function test_find_by_id_returns_null_when_not_found(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( null );
		$GLOBALS['wpdb'] = $wpdb;

		$this->assertNull( PeopleRepository::find_by_id( 999 ) );
	}
}
