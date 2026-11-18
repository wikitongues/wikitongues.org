<?php
use WP_Mock\Tools\TestCase;

class StampAirtableIdsTest extends TestCase {

	private function post( string $title, string $record_id = '' ): array {
		return array(
			'title'     => $title,
			'record_id' => $record_id,
		);
	}

	public function test_stamps_exact_title_matches() {
		$plan = wt_plan_airtable_id_stamps(
			array(
				array(
					'id'   => 'recAAAAAAAAAAAAAA',
					'name' => 'Greece',
				),
			),
			array( 10 => $this->post( 'Greece' ) )
		);

		$this->assertSame( array( 10 => 'recAAAAAAAAAAAAAA' ), $plan['stamp'] );
		$this->assertSame( array(), $plan['unstamped_posts'] );
	}

	public function test_second_run_plans_nothing() {
		$plan = wt_plan_airtable_id_stamps(
			array(
				array(
					'id'   => 'recAAAAAAAAAAAAAA',
					'name' => 'Greece',
				),
			),
			array( 10 => $this->post( 'Greece', 'recAAAAAAAAAAAAAA' ) )
		);

		$this->assertSame( array(), $plan['stamp'] );
		$this->assertSame( 1, $plan['already'] );
	}

	public function test_never_overwrites_a_different_id() {
		$plan = wt_plan_airtable_id_stamps(
			array(
				array(
					'id'   => 'recAAAAAAAAAAAAAA',
					'name' => 'Greece',
				),
			),
			array( 10 => $this->post( 'Greece', 'recZZZZZZZZZZZZZZ' ) )
		);

		$this->assertSame( array(), $plan['stamp'] );
		$this->assertCount( 1, $plan['conflicts'] );
	}

	public function test_never_gives_one_id_to_two_posts() {
		$plan = wt_plan_airtable_id_stamps(
			array(
				array(
					'id'   => 'recAAAAAAAAAAAAAA',
					'name' => 'Greece',
				),
			),
			array(
				10 => $this->post( 'Hellas', 'recAAAAAAAAAAAAAA' ),
				11 => $this->post( 'Greece' ),
			)
		);

		$this->assertSame( array(), $plan['stamp'] );
		$this->assertCount( 1, $plan['conflicts'] );
	}

	public function test_reports_unmatched_ambiguous_and_leftover_posts() {
		$plan = wt_plan_airtable_id_stamps(
			array(
				array(
					'id'   => 'recAAAAAAAAAAAAAA',
					'name' => 'Atlantis',
				),
				array(
					'id'   => 'recBBBBBBBBBBBBBB',
					'name' => 'Georgia',
				),
			),
			array(
				10 => $this->post( 'Georgia' ),
				11 => $this->post( 'Georgia' ),
				12 => $this->post( 'Kosovo' ),
			)
		);

		$this->assertSame( array( 'Atlantis' ), $plan['unmatched'] );
		$this->assertSame( array( 'Georgia' ), $plan['ambiguous'] );
		$this->assertSame( array( 'Georgia', 'Georgia', 'Kosovo' ), $plan['unstamped_posts'] );
	}
}
