<?php
use WP_Mock\Tools\TestCase;
use WT\Maps\Shape_Table;

class MapSetsTest extends TestCase {

	private const US = 'recErwkQpwxGCXW5F';

	private const HAWAII = 'recJdmrV257IxkekS';

	private const GUAM = 'recguDPgV3cIXLO34';

	private const NMI = 'rec8BH6e7SD1A1J5I';

	private const ARUBA = 'recEEEEEEEEEEEEEE';

	private const HAWAIIAN = 'recUC4moS37PaSogS';

	private function table(): Shape_Table {
		$place = fn( string $code ) => array( 'shapes' => array( $code ) );
		return Shape_Table::from_array(
			array(
				'natural_earth' => '5.1.2',
				'places'        => array(
					self::US     => $place( 'countries:USA' ),
					self::HAWAII => $place( 'subunits:USH' ),
					self::GUAM   => $place( 'units:GUM' ),
					self::NMI    => $place( 'units:MNP' ),
				),
				'focus'         => array(
					self::HAWAIIAN => array( 'places' => array( self::HAWAII ) ),
				),
			)
		);
	}

	private function language( string $title, array $territories, string $record_id = '' ): array {
		return array(
			'record_id'   => $record_id,
			'title'       => $title,
			'territories' => $territories,
		);
	}

	public function test_one_place_keys_on_its_record_id() {
		$this->assertSame( self::GUAM, wt_maps_set_key( array( self::GUAM ) ) );
		$this->assertSame( self::GUAM, wt_maps_set_key( array( self::GUAM, self::GUAM ) ) );
		$this->assertSame( '', wt_maps_set_key( array() ) );
	}

	public function test_several_places_key_on_a_hash_independent_of_order() {
		$key = wt_maps_set_key( array( self::GUAM, self::NMI ) );

		$this->assertSame( $key, wt_maps_set_key( array( self::NMI, self::GUAM ) ) );
		$this->assertMatchesRegularExpression( '/^set-[0-9a-f]{12}$/', $key );
		$this->assertNotSame( $key, wt_maps_set_key( array( self::GUAM, self::US ) ) );
	}

	public function test_focus_replaces_territory_links() {
		$table = $this->table();

		$this->assertSame( array( self::HAWAII ), wt_maps_language_places( $table, self::HAWAIIAN, array( self::US ) ) );
		$this->assertSame( array( self::US ), wt_maps_language_places( $table, 'recNOTFOCUSEDxxxx', array( self::US ) ) );
		$this->assertSame( array( self::NMI, self::GUAM ), wt_maps_language_places( $table, '', array( self::GUAM, self::NMI, self::GUAM ) ), 'sorted, deduplicated' );
	}

	public function test_origins_are_drawn_only_when_there_are_no_territories() {
		$table = $this->table();

		$this->assertSame( array( self::US ), wt_maps_language_places( $table, '', array(), array( self::US ) ), 'Eteocypriot: no territories, an origin' );
		$this->assertSame( array( self::GUAM ), wt_maps_language_places( $table, '', array( self::GUAM ), array( self::US ) ), 'territories win' );
		$this->assertSame( array( self::HAWAII ), wt_maps_language_places( $table, self::HAWAIIAN, array(), array( self::US ) ), 'the focus wins' );

		$plan = wt_maps_plan_sets( $table, array( $this->language( 'Origin only', array() ) + array( 'origins' => array( self::US ) ) ) );
		$this->assertSame( array( self::US ), array_keys( $plan['sets'] ) );
		$this->assertSame( 0, $plan['no_places'] );
	}

	public function test_plan_groups_languages_by_set_and_never_draws_part_of_one() {
		$plan = wt_maps_plan_sets(
			$this->table(),
			array(
				$this->language( 'Hawaiian', array( self::US ), self::HAWAIIAN ),
				$this->language( 'Chamorro', array( self::GUAM, self::NMI ) ),
				$this->language( 'Other Marianas', array( self::NMI, self::GUAM ) ),
				$this->language( 'English', array( self::US ) ),
				$this->language( 'Papiamento', array( self::ARUBA, self::US ) ),
				$this->language( 'Esperanto', array() ),
			)
		);

		$expected = array(
			self::HAWAII => array(
				'places'    => array( self::HAWAII ),
				'languages' => 1,
			),
			self::US     => array(
				'places'    => array( self::US ),
				'languages' => 1,
			),
			wt_maps_set_key( array( self::GUAM, self::NMI ) ) => array(
				'places'    => array( self::NMI, self::GUAM ),
				'languages' => 2,
			),
		);
		ksort( $expected );
		$this->assertSame( $expected, $plan['sets'] );
		$this->assertSame( array( 'Papiamento: no shape for ' . self::ARUBA ), $plan['unmapped'] );
		$this->assertSame( 1, $plan['no_places'] );
	}

	public function test_extra_sets_join_the_plan_with_their_labels() {
		$plan = wt_maps_plan_sets( $this->table(), array( $this->language( 'Chamorro', array( self::GUAM, self::NMI ) ) ) );
		$plan = wt_maps_add_extra_sets(
			$this->table(),
			$plan,
			array(
				'Region A' => array( self::NMI, self::GUAM ),
				'Region B' => array( self::US, self::HAWAII ),
				'Region C' => array( self::US, self::ARUBA ),
				'Region D' => array(),
			)
		);

		$shared = wt_maps_set_key( array( self::GUAM, self::NMI ) );
		$this->assertSame( 1, $plan['sets'][ $shared ]['languages'], 'a set a language uses keeps its count' );
		$this->assertSame( array( 'Region A' ), $plan['sets'][ $shared ]['pages'] );
		$this->assertSame( 0, $plan['sets'][ wt_maps_set_key( array( self::US, self::HAWAII ) ) ]['languages'] );
		$this->assertSame( array( 'Region C: no shape for ' . self::ARUBA ), $plan['unmapped'], 'a place with no shape means no map, never a partial one' );
		$this->assertCount( 2, $plan['sets'] );
	}

	public function test_a_page_can_ask_for_the_locator_globe() {
		$plan = wt_maps_plan_sets( $this->table(), array() );
		$plan = wt_maps_add_extra_sets(
			$this->table(),
			$plan,
			array(
				'Region: Micronesia' => array(
					'places' => array( self::GUAM, self::NMI ),
					'globe'  => true,
				),
				'Territory: Guam'    => array( self::GUAM ),
				'Rights region'      => array( self::NMI, self::GUAM ),
			)
		);

		$region = $plan['sets'][ wt_maps_set_key( array( self::GUAM, self::NMI ) ) ];
		$this->assertTrue( $region['globe'] );
		$this->assertSame( array( 'Region: Micronesia', 'Rights region' ), $region['pages'], 'a shared set keeps the globe' );
		$this->assertArrayNotHasKey( 'globe', $plan['sets'][ self::GUAM ], 'a list of IDs asks for nothing' );
	}

	public function test_a_view_fixes_the_frame_and_groups_in_its_own_set() {
		$plan = wt_maps_add_extra_sets(
			$this->table(),
			wt_maps_plan_sets( $this->table(), array() ),
			array(
				'Continent: Oceania' => array(
					'places' => array( self::NMI, self::GUAM ),
					'view'   => 'continent',
					'frame'  => array(
						'center' => array( 162, -10 ),
						'radius' => 30,
					),
					'groups' => array(
						self::NMI  => 1,
						self::GUAM => 1,
					),
				),
			)
		);

		$key = wt_maps_set_key( array( self::GUAM, self::NMI ), 'continent' );
		$this->assertStringEndsWith( '-continent', $key );
		$this->assertArrayNotHasKey( wt_maps_set_key( array( self::GUAM, self::NMI ) ), $plan['sets'], 'a view never changes the plain set' );
		$set = $plan['sets'][ $key ];
		$this->assertSame( 30, $set['frame']['radius'] );
		$this->assertSame( array( self::NMI, self::GUAM ), array_keys( $set['groups'] ), 'groups are sorted, so the file is stable' );
	}

	public function test_the_build_report_names_the_pages_of_an_unbuilt_set() {
		$report = wt_maps_build_report(
			array(
				'set-abc' => array(
					'places'    => array( self::US, self::HAWAII ),
					'languages' => 0,
					'pages'     => array( 'Region B' ),
				),
			),
			array()
		);

		$this->assertSame( array( 'set-abc (Region B)' ), $report['unbuilt'] );
	}
}
