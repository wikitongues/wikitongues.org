<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\IntakeRepository;

class IntakeRepositoryTest extends TestCase {

	// -------------------------------------------------------------------------
	// get_answered_keys()
	// -------------------------------------------------------------------------

	public function test_get_answered_keys_returns_empty_for_no_rows(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array() );
		$GLOBALS['wpdb'] = $wpdb;

		$result = IntakeRepository::get_answered_keys( 1, array( 'community', 'organization' ) );
		$this->assertSame( array(), $result );
	}

	public function test_get_answered_keys_returns_matching_subset(): void {
		$row            = new stdClass();
		$row->responses = '{"community":"speaker","use_case":"research"}';

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( $row ) );
		$GLOBALS['wpdb'] = $wpdb;

		$result = IntakeRepository::get_answered_keys( 1, array( 'community', 'organization' ) );
		$this->assertSame( array( 'community' ), $result );
	}

	public function test_get_answered_keys_returns_all_when_both_answered(): void {
		$row            = new stdClass();
		$row->responses = '{"community":"speaker","organization":"Wikitongues","use_case":"research"}';

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( $row ) );
		$GLOBALS['wpdb'] = $wpdb;

		$result = IntakeRepository::get_answered_keys( 1, array( 'community', 'organization' ) );
		$this->assertSame( array( 'community', 'organization' ), $result );
	}

	public function test_get_answered_keys_ignores_malformed_json_rows(): void {
		$bad             = new stdClass();
		$bad->responses  = 'not-json';
		$good            = new stdClass();
		$good->responses = '{"community":"contributor"}';

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( $bad, $good ) );
		$GLOBALS['wpdb'] = $wpdb;

		$result = IntakeRepository::get_answered_keys( 1, array( 'community', 'organization' ) );
		$this->assertSame( array( 'community' ), $result );
	}
}
