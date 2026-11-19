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
}
