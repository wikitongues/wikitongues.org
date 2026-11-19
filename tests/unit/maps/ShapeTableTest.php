<?php
use WP_Mock\Tools\TestCase;
use WT\Maps\Shape_Table;

class ShapeTableTest extends TestCase {

	private const HAWAII = 'recJdmrV257IxkekS';

	private const GUAM = 'recguDPgV3cIXLO34';

	private const PALESTINE = 'recAAAAAAAAAAAAAA';

	private const HAWAIIAN = 'recUC4moS37PaSogS';

	private function table( array $places = array(), array $focus = array() ): Shape_Table {
		return Shape_Table::from_array(
			array(
				'natural_earth' => '5.1.2',
				'places'        => $places + array(
					self::HAWAII    => array(
						'name'   => 'Hawaii',
						'shapes' => array( 'subunits:USH' ),
					),
					self::GUAM      => array(
						'name'   => 'Guam',
						'shapes' => array( 'units:GUM' ),
					),
					self::PALESTINE => array(
						'name'   => 'Palestine',
						'shapes' => array( 'units:GAZ', 'units:WEB', 'units:GAZ' ),
					),
				),
				'focus'         => $focus,
			)
		);
	}

	public function test_shapes_are_looked_up_by_record_id() {
		$table = $this->table();

		$this->assertSame( array( 'subunits:USH' ), $table->shapes( self::HAWAII ) );
		$this->assertSame( array( 'units:GAZ', 'units:WEB' ), $table->shapes( self::PALESTINE ), 'repeats are dropped' );
		$this->assertSame( array(), $table->shapes( 'Hawaii' ), 'names never match' );
		$this->assertSame( array(), $table->shapes( 'recZZZZZZZZZZZZZZ' ) );
		$this->assertSame( '5.1.2', $table->natural_earth_version() );
		$this->assertSame( array(), $table->problems() );
	}

	public function test_parse_ref_accepts_only_known_layers_and_three_letter_codes() {
		$this->assertSame(
			array(
				'layer' => 'breakaway',
				'code'  => 'B35',
			),
			Shape_Table::parse_ref( 'breakaway:B35' )
		);
		$this->assertNull( Shape_Table::parse_ref( 'USH' ) );
		$this->assertNull( Shape_Table::parse_ref( 'admin1:USH' ) );
		$this->assertNull( Shape_Table::parse_ref( 'units:gum' ) );
		$this->assertNull( Shape_Table::parse_ref( 'units:GAZ,WEB' ) );
	}

	public function test_bad_entries_are_left_out_and_reported() {
		$table = $this->table(
			array(
				'Hawaii_US'         => array(
					'name'   => 'Hawaii',
					'shapes' => array( 'subunits:USH' ),
				),
				'recBBBBBBBBBBBBBB' => array(
					'name'   => 'Wales',
					'shapes' => array( 'WLS' ),
				),
				'recCCCCCCCCCCCCCC' => array(
					'name'   => 'Empty',
					'shapes' => array(),
				),
			)
		);

		$this->assertSame( array(), $table->shapes( 'Hawaii_US' ) );
		$this->assertSame( array(), $table->shapes( 'recBBBBBBBBBBBBBB' ) );
		$this->assertNotContains( 'recCCCCCCCCCCCCCC', $table->place_ids() );
		$this->assertCount( 4, $table->problems(), implode( "\n", $table->problems() ) );
	}

	public function test_focus_keeps_only_places_with_shapes() {
		$table = $this->table(
			array(),
			array(
				self::HAWAIIAN      => array(
					'name'   => 'Hawaiian',
					'places' => array( self::HAWAII, 'recZZZZZZZZZZZZZZ' ),
				),
				'recDDDDDDDDDDDDDD' => array(
					'name'   => 'Nothing usable',
					'places' => array( 'recZZZZZZZZZZZZZZ' ),
				),
			)
		);

		$this->assertSame( array( self::HAWAII ), $table->focus( self::HAWAIIAN ) );
		$this->assertSame( array(), $table->focus( 'recDDDDDDDDDDDDDD' ) );
		$this->assertSame( array( self::HAWAIIAN ), $table->focus_language_ids() );
		$this->assertCount( 3, $table->problems(), implode( "\n", $table->problems() ) );
	}

	public function test_unreadable_file_gives_an_empty_table_with_a_problem() {
		$table = Shape_Table::from_file( __DIR__ . '/does-not-exist.json' );

		$this->assertSame( array(), $table->place_ids() );
		$this->assertCount( 1, $table->problems() );
	}

	public function test_doctor_reports_gaps_on_both_sides() {
		$table = $this->table(
			array(),
			array(
				self::HAWAIIAN => array(
					'name'   => 'Hawaiian',
					'places' => array( self::HAWAII ),
				),
			)
		);

		$report = wt_maps_doctor(
			$table,
			array(
				self::HAWAII        => array(
					'title'  => 'Hawaii',
					'type'   => 'subdivisions',
					'status' => 'publish',
				),
				self::GUAM          => array(
					'title'  => 'Guam',
					'type'   => 'territories',
					'status' => 'draft',
				),
				'recEEEEEEEEEEEEEE' => array(
					'title'  => 'Aruba',
					'type'   => 'territories',
					'status' => 'publish',
				),
				'recFFFFFFFFFFFFFF' => array(
					'title'  => 'Oaxaca',
					'type'   => 'subdivisions',
					'status' => 'publish',
				),
			),
			array()
		);

		$this->assertSame( array(), $report['table'] );
		$this->assertSame( array( 'Aruba (recEEEEEEEEEEEEEE)' ), $report['missing'], 'subdivisions without a shape are fine' );
		$this->assertSame( array( 'Palestine (' . self::PALESTINE . ')' ), $report['unknown'] );
		$this->assertSame( array( 'Guam (' . self::GUAM . '): draft' ), $report['unpublished'] );
		$this->assertSame( array( self::HAWAIIAN ), $report['languages'] );
		$this->assertTrue( wt_maps_doctor_has_errors( $report ) );
	}

	public function test_doctor_is_clean_when_everything_matches() {
		$report = wt_maps_doctor(
			Shape_Table::from_array(
				array(
					'natural_earth' => '5.1.2',
					'places'        => array(
						self::GUAM => array(
							'name'   => 'Guam',
							'shapes' => array( 'units:GUM' ),
						),
					),
				)
			),
			array(
				self::GUAM => array(
					'title'  => 'Guam',
					'type'   => 'territories',
					'status' => 'draft',
				),
			),
			array()
		);

		$this->assertFalse( wt_maps_doctor_has_errors( $report ), 'a draft territory is informational only' );
	}

	public function test_committed_table_is_clean() {
		$table = Shape_Table::from_file( __DIR__ . '/../../../wp-content/plugins/wt-maps/data/shapes.json' );

		$this->assertSame( array(), $table->problems() );
		$this->assertSame( array( 'subunits:USH' ), $table->shapes( self::HAWAII ) );
		$this->assertSame( array( self::HAWAII ), $table->focus( self::HAWAIIAN ) );
		$this->assertGreaterThanOrEqual( 218, count( $table->place_ids() ) );
	}

	public function test_build_report_lists_sets_without_maps_and_maps_without_sets() {
		$report = wt_maps_build_report(
			array(
				self::GUAM         => array(
					'places'    => array( self::GUAM ),
					'languages' => 3,
				),
				self::HAWAII       => array(
					'places'    => array( self::HAWAII ),
					'languages' => 1,
				),
				'set-0123456789ab' => array(
					'places'    => array( self::GUAM, self::HAWAII ),
					'languages' => 2,
				),
			),
			array( self::GUAM, 'recOLDOLDOLDOLDOL' )
		);

		$this->assertSame( array( self::HAWAII . ' (1 language)', 'set-0123456789ab (2 languages)' ), $report['unbuilt'] );
		$this->assertSame( array( 'recOLDOLDOLDOLDOL' ), $report['unused'] );
		$this->assertTrue( wt_maps_doctor_has_errors( $report ) );
		$this->assertFalse(
			wt_maps_doctor_has_errors(
				array(
					'unbuilt' => array(),
					'unused'  => array( 'recOLDOLDOLDOLDOL' ),
				)
			),
			'an unused map is informational'
		);
	}
}
