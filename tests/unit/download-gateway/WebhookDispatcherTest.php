<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\WebhookDispatcher;

class WebhookDispatcherTest extends TestCase {

	// -------------------------------------------------------------------------
	// enqueue()
	// -------------------------------------------------------------------------

	public function test_enqueue_inserts_pending_row_with_correct_columns(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'insert' )
			->once()
			->withArgs(
				function ( string $table, array $data ): bool {
					return str_ends_with( $table, 'gateway_webhook_delivery' )
						&& $data['event_id'] === 42
						&& $data['endpoint_url'] === 'https://hook.make.com/test'
						&& $data['status'] === 'pending'
						&& $data['attempts'] === 0
						&& isset( $data['payload'] )
						&& isset( $data['next_attempt_at'] )
						&& isset( $data['created_at'] )
						&& null === $data['last_attempt_at'];
				}
			)
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'wp_json_encode', array( 'return' => '{"type":"person"}' ) );
		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-23 10:00:00' ) );

		WebhookDispatcher::enqueue( 42, 'https://hook.make.com/test', array( 'type' => 'person' ) );
		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// dispatch_pending() — skip future rows
	// -------------------------------------------------------------------------

	public function test_dispatch_pending_skips_rows_with_future_next_attempt(): void {
		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		// Returns empty — the SQL WHERE clause already filtered future rows.
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array() );
		// No update expected — nothing to process.
		$wpdb->shouldNotReceive( 'update' );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-23 10:00:00' ) );

		WebhookDispatcher::dispatch_pending();
		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// attempt() — 2xx response → delivered
	// -------------------------------------------------------------------------

	public function test_2xx_response_marks_row_delivered(): void {
		$row               = new stdClass();
		$row->id           = 1;
		$row->endpoint_url = 'https://hook.make.com/test';
		$row->payload      = '{"type":"download"}';
		$row->attempts     = 0;

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( $row ) );
		$wpdb->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( string $table, array $data ): bool {
					return $data['status'] === 'delivered'
						&& $data['attempts'] === 1
						&& isset( $data['last_attempt_at'] )
						&& $data['next_attempt_at'] === null;
				}
			)
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-23 10:00:00' ) );
		WP_Mock::userFunction( 'wp_remote_post', array( 'return' => array( 'response' => array( 'code' => 200 ) ) ) );
		WP_Mock::userFunction( 'is_wp_error', array( 'return' => false ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 200 ) );

		WebhookDispatcher::dispatch_pending();
		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// attempt() — non-2xx → failed with backoff
	// -------------------------------------------------------------------------

	public function test_non_2xx_marks_row_failed_with_next_attempt(): void {
		$row               = new stdClass();
		$row->id           = 2;
		$row->endpoint_url = 'https://hook.make.com/test';
		$row->payload      = '{"type":"download"}';
		$row->attempts     = 0;

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( $row ) );
		$wpdb->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( string $table, array $data ): bool {
					return $data['status'] === 'failed'
						&& $data['attempts'] === 1
						&& isset( $data['last_attempt_at'] )
						&& isset( $data['next_attempt_at'] );
				}
			)
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-23 10:00:00' ) );
		WP_Mock::userFunction( 'wp_remote_post', array( 'return' => array( 'response' => array( 'code' => 500 ) ) ) );
		WP_Mock::userFunction( 'is_wp_error', array( 'return' => false ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 500 ) );

		WebhookDispatcher::dispatch_pending();
		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// attempt() — WP_Error → treated as non-2xx failure
	// -------------------------------------------------------------------------

	public function test_wp_error_response_treated_as_failure(): void {
		$row               = new stdClass();
		$row->id           = 3;
		$row->endpoint_url = 'https://hook.make.com/test';
		$row->payload      = '{"type":"download"}';
		$row->attempts     = 0;

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( $row ) );
		$wpdb->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( string $table, array $data ): bool {
					return $data['status'] === 'failed'
						&& $data['attempts'] === 1;
				}
			)
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-23 10:00:00' ) );
		WP_Mock::userFunction( 'wp_remote_post', array( 'return' => new \WP_Error( 'http_request_failed', 'cURL error' ) ) );
		WP_Mock::userFunction( 'is_wp_error', array( 'return' => true ) );

		WebhookDispatcher::dispatch_pending();
		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// attempt() — MAX_ATTEMPTS reached → dead
	// -------------------------------------------------------------------------

	public function test_max_attempts_marks_row_dead(): void {
		$row               = new stdClass();
		$row->id           = 4;
		$row->endpoint_url = 'https://hook.make.com/test';
		$row->payload      = '{"type":"download"}';
		$row->attempts     = WebhookDispatcher::MAX_ATTEMPTS - 1; // one more → dead

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( $row ) );
		$wpdb->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( string $table, array $data ): bool {
					return $data['status'] === 'dead'
						&& $data['attempts'] === WebhookDispatcher::MAX_ATTEMPTS
						&& $data['next_attempt_at'] === null;
				}
			)
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-23 10:00:00' ) );
		WP_Mock::userFunction( 'wp_remote_post', array( 'return' => array( 'response' => array( 'code' => 503 ) ) ) );
		WP_Mock::userFunction( 'is_wp_error', array( 'return' => false ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 503 ) );

		WebhookDispatcher::dispatch_pending();
		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// backoff() — delay schedule via next_attempt_at values
	// -------------------------------------------------------------------------

	/**
	 * @dataProvider backoff_delay_provider
	 */
	public function test_backoff_sets_correct_delay( int $prior_attempts, int $expected_delay_seconds ): void {
		$row               = new stdClass();
		$row->id           = 10;
		$row->endpoint_url = 'https://hook.make.com/test';
		$row->payload      = '{"type":"download"}';
		$row->attempts     = $prior_attempts;

		$captured_next_attempt = null;

		/** @var \Mockery\MockInterface&\wpdb $wpdb */
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT_SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( $row ) );
		$wpdb->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( string $table, array $data ) use ( &$captured_next_attempt ): bool {
					$captured_next_attempt = $data['next_attempt_at'] ?? null;
					return true;
				}
			)
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		WP_Mock::userFunction( 'current_time', array( 'return' => '2026-03-23 10:00:00' ) );
		WP_Mock::userFunction( 'wp_remote_post', array( 'return' => array( 'response' => array( 'code' => 500 ) ) ) );
		WP_Mock::userFunction( 'is_wp_error', array( 'return' => false ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 500 ) );

		$before = time();
		WebhookDispatcher::dispatch_pending();
		$after = time();

		$this->assertNotNull( $captured_next_attempt, 'next_attempt_at should be set on failure' );

		$scheduled_ts = strtotime( $captured_next_attempt );
		$this->assertGreaterThanOrEqual(
			$before + $expected_delay_seconds,
			$scheduled_ts,
			"Attempt {$prior_attempts}: expected delay >= {$expected_delay_seconds}s"
		);
		$this->assertLessThanOrEqual(
			$after + $expected_delay_seconds + 2,
			$scheduled_ts,
			"Attempt {$prior_attempts}: expected delay within 2s tolerance of {$expected_delay_seconds}s"
		);
	}

	/**
	 * @return array<string,array{int,int}>
	 */
	public static function backoff_delay_provider(): array {
		return array(
			'attempt 1 - 60s'   => array( 0, 60 ),
			'attempt 2 - 300s'  => array( 1, 300 ),
			'attempt 3 - 1800s' => array( 2, 1800 ),
			'attempt 4 - 7200s' => array( 3, 7200 ),
		);
	}
}
