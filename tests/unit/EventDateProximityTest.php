<?php
use WP_Mock\Tools\TestCase;

class EventDateProximityTest extends TestCase {

	public function test_far_future_date_has_no_proximity_prefix() {
		$result = format_event_date_with_proximity( '2090-06-15 10:00:00' );

		$this->assertStringNotContainsString( 'Next', $result );
		$this->assertStringNotContainsString( 'Last', $result );
		$this->assertStringNotContainsString( 'Today', $result );
	}

	public function test_far_past_date_has_no_proximity_prefix() {
		$result = format_event_date_with_proximity( '2000-01-01 10:00:00' );

		$this->assertStringNotContainsString( 'Next', $result );
		$this->assertStringNotContainsString( 'Last', $result );
		$this->assertStringNotContainsString( 'Today', $result );
	}

	public function test_date_three_days_from_now_has_next_prefix() {
		$date   = gmdate( 'Y-m-d', strtotime( '+3 days' ) ) . ' 12:00:00';
		$result = format_event_date_with_proximity( $date );

		$this->assertStringContainsString( 'Next', $result );
	}

	public function test_date_three_days_ago_has_last_prefix() {
		$date   = gmdate( 'Y-m-d', strtotime( '-3 days' ) ) . ' 12:00:00';
		$result = format_event_date_with_proximity( $date );

		$this->assertStringContainsString( 'Last', $result );
	}

	public function test_today_has_today_prefix() {
		$date   = gmdate( 'Y-m-d' ) . ' 12:00:00';
		$result = format_event_date_with_proximity( $date );

		$this->assertStringContainsString( 'Today', $result );
	}

	public function test_output_contains_formatted_gmdate() {
		$result = format_event_date_with_proximity( '2090-06-15 10:00:00' );

		$this->assertStringContainsString( '15 June 2090', $result );
	}
}
