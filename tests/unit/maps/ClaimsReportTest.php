<?php
use WP_Mock\Tools\TestCase;
use WT\Maps\Shape_Table;

/**
 * `doctor`'s check of the claims table (data/claims.json).
 */
class ClaimsReportTest extends TestCase {

	private const CHINA = 'recpwmL8YQeWhEyJ1';

	private const INDIA = 'reckMaHEYWWoejWWB';

	private const TAIWAN = 'recgU0oaqvwT3YrW0';

	private function table(): Shape_Table {
		return Shape_Table::from_array(
			array(
				'natural_earth' => '5.1.2',
				'places'        => array(
					self::CHINA  => array(
						'name'   => 'China',
						'shapes' => array( 'countries:CHN' ),
					),
					self::INDIA  => array(
						'name'   => 'India',
						'shapes' => array( 'countries:IND' ),
					),
					self::TAIWAN => array(
						'name'   => 'Taiwan',
						'shapes' => array( 'units:TWN' ),
					),
				),
				'focus'         => array(),
			)
		);
	}

	private function claims( array $changes, array $claimants = array( self::CHINA, self::INDIA, self::TAIWAN ) ): array {
		return array(
			'layers' => array(
				array(
					'id'   => 'natural-earth',
					'rows' => array(
						'B00' => array(
							'name'      => 'Arunachal Pradesh',
							'claimants' => $claimants,
						),
					),
				),
				array(
					'id'      => 'review',
					'changes' => $changes,
				),
			),
		);
	}

	public function test_a_clean_table_reports_nothing() {
		$report = wt_maps_claims_report(
			$this->claims(
				array(
					array(
						'area'   => 'B00',
						'drop'   => array( self::TAIWAN ),
						'reason' => 'Dormant claim',
					),
				)
			),
			$this->table()
		);
		$this->assertSame( array(), $report['claims'] );
		$this->assertSame( array(), $report['claims_noop'] );
	}

	public function test_unknown_claimants_missing_reasons_and_areas_are_errors() {
		$report = wt_maps_claims_report(
			$this->claims(
				array(
					array(
						'area'   => 'B00',
						'add'    => array( 'recZZZZZZZZZZZZZZ' ),
						'reason' => 'Test',
					),
					array(
						'area' => 'B00',
						'drop' => array( self::TAIWAN ),
					),
					array(
						'area'   => 'B99',
						'drop'   => array( self::TAIWAN ),
						'reason' => 'Test',
					),
				),
				array( self::CHINA, 'recYYYYYYYYYYYYYY' )
			),
			$this->table()
		);
		$this->assertSame(
			array(
				'natural-earth: B00: claimant recYYYYYYYYYYYYYY is not in the shape table',
				'review: B00 has no reason',
				'review: B00: claimant recZZZZZZZZZZZZZZ is not in the shape table',
				'review: B99 is not in layer 1',
			),
			$report['claims']
		);
		$this->assertTrue( wt_maps_doctor_has_errors( $report ) );
	}

	public function test_a_change_that_changes_nothing_is_informational() {
		$report = wt_maps_claims_report(
			$this->claims(
				array(
					array(
						'area'   => 'B00',
						'drop'   => array( self::TAIWAN ),
						'reason' => 'Dormant claim',
					),
				),
				array( self::CHINA, self::INDIA )
			),
			$this->table()
		);
		$this->assertSame( array( 'review: B00 changes nothing' ), $report['claims_noop'] );
		$this->assertFalse( wt_maps_doctor_has_errors( $report ) );
	}

	public function test_the_committed_table_is_clean() {
		$root   = dirname( __DIR__, 3 ) . '/wp-content/plugins/wt-maps/data/';
		$report = wt_maps_claims_report(
			json_decode( (string) file_get_contents( $root . 'claims.json' ), true ),
			Shape_Table::from_file( $root . 'shapes.json' )
		);
		$this->assertSame( array(), $report['claims'] );
		$this->assertSame( array(), $report['claims_noop'] );
	}
}
