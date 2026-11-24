<?php
use WP_Mock\Tools\TestCase;

/**
 * Territory and region map sets (includes/template/place-maps.php).
 */
class PlaceMapsTest extends TestCase {

	/**
	 * A region term (core isn't loaded in unit tests; Mockery stands in for WP_Term).
	 */
	private static function term( int $id, int $parent_id, string $name ): WP_Term {
		/** @var WP_Term $term */
		$term          = Mockery::mock( 'WP_Term' );
		$term->term_id = $id;
		$term->parent  = $parent_id;
		$term->name    = $name;
		$term->slug    = strtolower( str_replace( ' ', '-', $name ) );
		return $term;
	}

	public function setUp(): void {
		parent::setUp();
		// Post ID → record ID, as the plugin's wt_maps_record_ids() reads them.
		WP_Mock::userFunction(
			'wt_maps_record_ids',
			array(
				'return' => static fn( array $ids ): array => array_map( static fn( $id ) => 'rec' . $id, $ids ),
			)
		);
	}

	public function test_only_sub_regions_have_a_map() {
		$this->assertFalse( wt_region_has_map( self::term( 1, 0, 'Europe' ) ) );
		$this->assertTrue( wt_region_has_map( self::term( 2, 1, 'Eastern Europe' ) ) );
	}

	public function test_sets_are_one_per_territory_sub_region_and_continent() {
		$europe    = self::term( 1, 0, 'Europe' );
		$eastern   = self::term( 2, 1, 'Eastern Europe' );
		$one_place = self::term( 3, 5, 'One territory' );

		WP_Mock::userFunction(
			'get_posts',
			array(
				'return' => static function ( array $args ): array {
					if ( ! isset( $args['tax_query'] ) ) {
						return array( 10, 11, 12 ); // Every published territory.
					}
					$term = $args['tax_query'][0]['terms'][0];
					return 2 === $term ? array( 11, 12 ) : array( 10 );
				},
			)
		);
		WP_Mock::userFunction(
			'get_terms',
			array(
				'return' => static function ( array $args ) use ( $europe, $eastern, $one_place ): array {
					if ( isset( $args['parent'] ) ) {
						return 1 === $args['parent'] ? array( $eastern ) : array();
					}
					return array( $europe, $eastern, $one_place );
				},
			)
		);
		WP_Mock::userFunction( 'get_the_title', array( 'return' => static fn( int $id ): string => 'T' . $id ) );

		$this->assertSame(
			array(
				'Territory: T10'         => array( 'rec10' ),
				'Territory: T11'         => array( 'rec11' ),
				'Territory: T12'         => array( 'rec12' ),
				'Continent: Europe'      => array(
					'places' => array( 'rec11', 'rec12' ),
					'view'   => 'continent',
					'groups' => array(
						'rec11' => 1,
						'rec12' => 1,
					),
					'frame'  => wt_continent_frames()['europe'],
				),
				'Region: Eastern Europe' => array(
					'places' => array( 'rec11', 'rec12' ),
					'globe'  => true,
					'halo'   => true,
					'rings'  => 'detached',
				),
			),
			wt_place_map_sets( array() )
		);
	}

	public function test_continent_without_a_frame_has_no_map() {
		WP_Mock::userFunction( 'wt_map_places', array( 'times' => 0 ) );
		$this->assertSame( '', wt_region_map( self::term( 1, 0, 'Atlantis' ), array( 10, 11 ) ) );
	}

	public function test_continent_map_is_its_own_view() {
		$europe  = self::term( 1, 0, 'Europe' );
		$eastern = self::term( 2, 1, 'Eastern Europe' );
		$western = self::term( 3, 1, 'Western Europe' );
		WP_Mock::userFunction( 'get_terms', array( 'return' => array( $eastern, $western ) ) );
		WP_Mock::userFunction(
			'get_posts',
			array( 'return' => static fn( array $args ): array => 2 === $args['tax_query'][0]['terms'][0] ? array( 11 ) : array( 12, 13 ) )
		);
		WP_Mock::userFunction( 'wt_prefix_the', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction(
			'wt_map_places',
			array(
				'times'  => 1,
				'args'   => array(
					array(
						'places'  => array( 'rec11', 'rec12', 'rec13' ),
						'primary' => array( 'rec11', 'rec12', 'rec13' ),
						'title'   => 'Map of Europe',
						'class'   => 'wt-place-map',
						'view'    => 'continent',
					),
				),
				'return' => '<figure></figure>',
			)
		);

		$this->assertSame(
			array(
				'rec11' => 1,
				'rec12' => 2,
				'rec13' => 2,
			),
			wt_continent_set( $europe )['groups'] ?? null
		);
		$this->assertSame( '<figure></figure>', wt_region_map( $europe ) );
	}

	public function test_region_map_draws_its_territories_as_primary() {
		WP_Mock::userFunction( 'wt_prefix_the', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction(
			'wt_map_places',
			array(
				'times'  => 1,
				'args'   => array(
					array(
						'places'  => array( 'rec11', 'rec12' ),
						'primary' => array( 'rec11', 'rec12' ),
						'title'   => 'Map of Polynesia',
						'class'   => 'wt-place-map',
						'view'    => '',
					),
				),
				'return' => '<figure></figure>',
			)
		);

		$this->assertSame( '<figure></figure>', wt_region_map( self::term( 2, 1, 'Polynesia' ), array( 11, 12 ) ) );
	}

	public function test_territory_sidebar_shows_its_whole_sub_region() {
		WP_Mock::userFunction( 'get_the_terms', array( 'return' => array( self::term( 2, 1, 'Polynesia' ) ) ) );
		WP_Mock::userFunction( 'get_posts', array( 'return' => array( 11, 12 ) ) );
		WP_Mock::userFunction( 'wt_prefix_the', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction(
			'wt_map_places',
			array(
				'times'  => 1,
				'args'   => array(
					array(
						'places'  => array( 'rec11', 'rec12' ),
						'primary' => array( 'rec11', 'rec12' ),
						'title'   => 'Map of Polynesia',
						'class'   => 'wt-place-map',
						'view'    => '',
					),
				),
				'return' => '<figure></figure>',
			)
		);

		$this->assertSame( '<figure></figure>', wt_territory_region_map( 11 ) );
	}

	public function test_territory_under_a_continent_has_no_region_map() {
		WP_Mock::userFunction( 'get_the_terms', array( 'return' => array( self::term( 1, 0, 'Australia & New Zealand' ) ) ) );
		$this->assertSame( '', wt_territory_region_map( 20 ) );
	}
}
