<?php
use WP_Mock\Tools\TestCase;

class SyncTerritoriesTest extends TestCase {

	private const US = 'recErwkQpwxGCXW5F';

	private function row( string $id, string $title, string $iso = '', string $nation = self::US ): array {
		return array(
			'airtable_id'   => $id,
			'post_title'    => $title,
			'iso_3166_code' => $iso,
			'nation'        => $nation,
		);
	}

	private function have( string $type, string $title, string $iso = '', string $nation = self::US ): array {
		return array(
			'type'   => $type,
			'title'  => $title,
			'iso'    => $iso,
			'nation' => $nation,
		);
	}

	private function plan( array $rows, array $current = array(), array $titles = array() ): array {
		return wt_plan_territory_sync(
			$rows,
			$current,
			$titles + array(
				'territories'  => array(),
				'subdivisions' => array(),
			),
			array( self::US => true )
		);
	}

	public function test_rows_reject_names_without_record_ids_and_repeats() {
		$parsed = wt_territory_rows(
			array(
				array(
					'airtable_id' => 'Guam_US',
					'post_title'  => 'Guam',
				),
				array(
					'airtable_id'   => 'recguDPgV3cIXLO34',
					'post_title'    => "Guam\xc2\xa0",
					'iso_3166_code' => ' gu ',
					'nation'        => 'recErwkQpwxGCXW5F, junk',
				),
				array(
					'airtable_id' => 'recguDPgV3cIXLO34',
					'post_title'  => 'Guam',
				),
			)
		);

		$this->assertCount( 1, $parsed['rows'] );
		$this->assertSame( 'Guam', $parsed['rows'][0]['post_title'] );
		$this->assertSame( 'GU', $parsed['rows'][0]['iso_3166_code'] );
		$this->assertSame( self::US, $parsed['rows'][0]['nation'] );
		$this->assertCount( 2, $parsed['invalid'] );
	}

	public function test_title_whitespace_is_collapsed_like_wordpress() {
		$parsed = wt_territory_rows(
			array(
				array(
					'airtable_id' => 'recAusv7kDp9BkfCs',
					'post_title'  => 'Shan  State ',
				),
			)
		);

		$this->assertSame( 'Shan State', $parsed['rows'][0]['post_title'] );
	}

	public function test_new_records_are_created_by_type() {
		$plan = $this->plan(
			array(
				$this->row( 'recJdmrV257IxkekS', 'Hawaii' ),
				$this->row( 'recS7hTex68dC0ctj', 'Puerto Rico', 'PR' ),
			)
		);

		$this->assertSame( array( 'Hawaii (recJdmrV257IxkekS) as subdivisions', 'Puerto Rico (recS7hTex68dC0ctj) as territories' ), $plan['create'] );
		$this->assertSame( array( 'recJdmrV257IxkekS', 'recS7hTex68dC0ctj' ), $plan['todo'] );
	}

	public function test_hand_made_page_with_the_title_is_stamped() {
		$plan = $this->plan( array( $this->row( 'recguDPgV3cIXLO34', 'Guam', 'GU' ) ), array(), array( 'territories' => array( 'Guam' => 1 ) ) );

		$this->assertSame( array( 'Guam (recguDPgV3cIXLO34)' ), $plan['stamp'] );
	}

	public function test_title_shared_by_two_unstamped_posts_is_skipped() {
		$plan = $this->plan( array( $this->row( 'recguDPgV3cIXLO34', 'Guam', 'GU' ) ), array(), array( 'territories' => array( 'Guam' => 2 ) ) );

		$this->assertSame( array( 'Guam (recguDPgV3cIXLO34)' ), $plan['ambiguous'] );
		$this->assertSame( array(), $plan['todo'] );
	}

	public function test_second_run_plans_nothing() {
		$plan = $this->plan(
			array( $this->row( 'recguDPgV3cIXLO34', 'Guam', 'GU' ) ),
			array( 'recguDPgV3cIXLO34' => $this->have( 'territories', 'Guam', 'GU' ) )
		);

		$this->assertSame( 1, $plan['unchanged'] );
		$this->assertSame( array(), $plan['todo'] );
	}

	public function test_changed_fields_are_named() {
		$plan = $this->plan(
			array( $this->row( 'recguDPgV3cIXLO34', 'Guåm', 'GU' ) ),
			array( 'recguDPgV3cIXLO34' => $this->have( 'territories', 'Guam', 'GU', '' ) )
		);

		$this->assertSame( array( 'Guåm (recguDPgV3cIXLO34): title, nation' ), $plan['update'] );
	}

	public function test_gaining_an_iso_code_changes_type() {
		$plan = $this->plan(
			array( $this->row( 'recJdmrV257IxkekS', 'Hawaii', 'HI' ) ),
			array( 'recJdmrV257IxkekS' => $this->have( 'subdivisions', 'Hawaii' ) )
		);

		$this->assertSame( array( 'Hawaii (recJdmrV257IxkekS): subdivisions → territories' ), $plan['type_change'] );
	}

	public function test_unresolvable_nation_counts_as_empty() {
		$plan = $this->plan(
			array( $this->row( 'recJdmrV257IxkekS', 'Hawaii', '', 'recUNKNOWNnation01' ) ),
			array( 'recJdmrV257IxkekS' => $this->have( 'subdivisions', 'Hawaii', '', '' ) )
		);

		$this->assertSame( 1, $plan['unchanged'] );
	}
}
