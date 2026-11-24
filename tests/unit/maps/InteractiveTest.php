<?php
use WP_Mock\Tools\TestCase;
use WT\Maps\Shape_Table;

/**
 * Map links and labels (wt-maps/includes/interactive.php, plan 3.2 step 2a).
 */
class InteractiveTest extends TestCase {

	private const FRANCE  = 'recc6lAeDMPAvqzgM';
	private const HAWAII  = 'recJdmrV257IxkekS';
	private const DRAFT   = 'recDRAFTPLACEAAAA';
	private const US_POST = 20641;

	/**
	 * A published post (core isn't loaded in unit tests; Mockery stands in for WP_Post).
	 */
	private static function post( int $id, string $type ): WP_Post {
		/** @var WP_Post $post */
		$post            = Mockery::mock( 'WP_Post' );
		$post->ID        = $id;
		$post->post_type = $type;
		return $post;
	}

	public function test_links_resolve_by_record_id_and_subdivisions_lead_to_their_nation() {
		$table = Shape_Table::from_array(
			array(
				'natural_earth' => '5.1.2',
				'places'        => array(
					self::DRAFT => array(
						'name'   => 'Draft Place',
						'shapes' => array( 'countries:XXX' ),
					),
				),
			)
		);
		WP_Mock::userFunction( 'wt_maps_shape_table', array( 'return' => $table ) );
		WP_Mock::userFunction(
			'get_posts',
			array(
				'return' => array( self::post( 1, 'territories' ), self::post( 2, 'subdivisions' ) ),
			)
		);
		$meta = array(
			1 => array( '_airtable_record_id' => self::FRANCE ),
			2 => array(
				'_airtable_record_id' => self::HAWAII,
				'nation'              => (string) self::US_POST,
			),
		);
		WP_Mock::userFunction( 'get_post_meta' )->andReturnUsing( static fn( $id, $key ) => $meta[ $id ][ $key ] ?? '' );
		$titles = array(
			1             => 'France',
			2             => 'Hawaii',
			self::US_POST => 'United States',
		);
		WP_Mock::userFunction( 'get_the_title' )->andReturnUsing( static fn( $p ) => $titles[ is_object( $p ) ? $p->ID : $p ] );
		WP_Mock::userFunction( 'get_permalink' )->andReturnUsing( static fn( $p ) => 'https://wikitongues.org/p/' . ( is_object( $p ) ? $p->ID : $p ) . '/' );
		WP_Mock::userFunction( 'get_post_status', array( 'return' => 'publish' ) );

		$links = wt_maps_place_links( array( self::FRANCE, self::HAWAII, self::DRAFT ) );

		$this->assertSame(
			array(
				'n' => 'France',
				'u' => 'https://wikitongues.org/p/1/',
			),
			$links[ self::FRANCE ]
		);
		$this->assertSame(
			array(
				'n' => 'Hawaii',
				'u' => 'https://wikitongues.org/p/20641/',
				'v' => 'the United States',
			),
			$links[ self::HAWAII ],
			'a subdivision has no page: its nation\'s, named'
		);
		$this->assertSame( array( 'n' => 'Draft Place' ), $links[ self::DRAFT ], 'no published page: a label from the shape table, no link' );
	}

	public function test_no_places_no_query() {
		WP_Mock::userFunction( 'get_posts', array( 'times' => 0 ) );
		$this->assertSame( array(), wt_maps_place_links( array() ) );
	}

	public function test_json_is_sorted_by_record_id() {
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( static fn( $v, $f = 0 ) => json_encode( $v, $f ) );
		$json = wt_maps_interaction_json(
			array(
				self::HAWAII => array( 'n' => 'Hawaii' ),
				self::FRANCE => array(
					'n' => 'France',
					'u' => 'https://wikitongues.org/territories/western-europe/france/',
				),
			)
		);
		$this->assertSame( '{"places":{"recJdmrV257IxkekS":{"n":"Hawaii"},"recc6lAeDMPAvqzgM":{"n":"France","u":"https://wikitongues.org/territories/western-europe/france/"}}}', $json );
	}

	public function test_continent_places_lead_to_their_sub_region() {
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( static fn( $v, $f = 0 ) => json_encode( $v, $f ) );
		$json = wt_maps_interaction_json(
			array( self::FRANCE => array( 'n' => 'France' ) ),
			array(
				array(
					'n'      => 'Eastern Europe',
					'u'      => 'https://wikitongues.org/territories/eastern-europe/',
					'places' => array( 'recPOLANDAAAAAAAA' ),
				),
				array(
					'n'      => 'Western Europe',
					'u'      => 'https://wikitongues.org/territories/western-europe/',
					'places' => array( self::FRANCE ),
				),
			)
		);
		$this->assertSame(
			'{"places":{"recPOLANDAAAAAAAA":{"g":1},"recc6lAeDMPAvqzgM":{"g":2}},"groups":{"1":{"n":"Eastern Europe","u":"https://wikitongues.org/territories/eastern-europe/"},"2":{"n":"Western Europe","u":"https://wikitongues.org/territories/western-europe/"}}}',
			$json,
			'no territory links: each place points at its group'
		);
	}

	public function test_groups_list_only_the_places_on_the_map() {
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( static fn( $v, $f = 0 ) => json_encode( $v, $f ) );
		$json = wt_maps_interaction_json(
			array(),
			array(
				array(
					'n'      => 'Western Europe',
					'u'      => '/w/',
					'places' => array( self::FRANCE, 'recBELGIUMAAAAAAA' ),
				),
				array(
					'n'      => 'Polynesia',
					'u'      => '/p/',
					'places' => array( 'recTAHITIAAAAAAAA' ),
				),
			),
			array( self::FRANCE )
		);
		$this->assertSame( '{"places":{"recc6lAeDMPAvqzgM":{"g":1}},"groups":{"1":{"n":"Western Europe","u":"/w/"}}}', $json, 'a group with nothing on the map is left out' );
	}

	public function test_tagged_places_lists_each_record_once() {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$this->markTestSkipped( 'WordPress HTML API not available (no local install at the repository root).' );
		}
		$svg = '<svg><g class="lands"><path class="land" d="M0 0" data-place="recSPAINAAAAAAAAA"/><path class="land" d="M1 1"/></g>'
			. '<g class="hls"><path class="hl" data-place="' . self::FRANCE . '"/></g><circle class="marker" data-place="' . self::FRANCE . '"/></svg>';
		$this->assertSame( array( 'recSPAINAAAAAAAAA', self::FRANCE ), wt_maps_tagged_places( $svg ) );
	}
}
