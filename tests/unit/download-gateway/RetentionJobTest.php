<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\RetentionJob;

class RetentionJobTest extends TestCase {

	// -------------------------------------------------------------------------
	// anonymize()
	// -------------------------------------------------------------------------

	public function test_anonymize_returns_count_of_anonymized_records(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE_SQL' );
		$wpdb->shouldReceive( 'query' )->once()->andReturn( 3 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-16 10:00:00' ) );
		WP_Mock::userFunction( 'get_option', array( 'return' => 24 ) );
		WP_Mock::userFunction( 'update_option', array( 'return' => true ) );

		$this->assertSame( 3, RetentionJob::anonymize() );
	}

	public function test_anonymize_returns_zero_when_no_records_due(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE_SQL' );
		$wpdb->shouldReceive( 'query' )->once()->andReturn( 0 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-16 10:00:00' ) );
		WP_Mock::userFunction( 'get_option', array( 'return' => 24 ) );
		WP_Mock::userFunction( 'update_option', array( 'return' => true ) );

		$this->assertSame( 0, RetentionJob::anonymize() );
	}

	public function test_anonymize_returns_zero_on_db_error(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE_SQL' );
		$wpdb->shouldReceive( 'query' )->once()->andReturn( false );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-16 10:00:00' ) );
		WP_Mock::userFunction( 'get_option', array( 'return' => 24 ) );
		WP_Mock::userFunction( 'update_option', array( 'return' => true ) );

		$this->assertSame( 0, RetentionJob::anonymize() );
	}
}
