<?php
use WP_Mock\Tools\TestCase;

/**
 * Plan 7.9 example cases for the typeahead search index.
 */
class TypeaheadIndexTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction( 'maybe_unserialize' )->andReturnUsing(
			static function ( $value ) {
				return is_string( $value ) && preg_match( '/^a:\d+:\{/', $value ) ? unserialize( $value ) : $value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			}
		);
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing(
			static function ( $data, $flags = 0 ) {
				return json_encode( $data, $flags );
			}
		);
	}

	private function raw(): array {
		return array(
			(object) array(
				'ID'                => 30,
				'post_name'         => 'tah',
				'post_title'        => 'Tahitian',
				'alternate_names'   => 'Tahitian, Reo Tahiti',
				'standard_name'     => 'Tahitian',
				'speakers_recorded' => 'a:0:{}',
			),
			(object) array(
				'ID'                => 10,
				'post_name'         => 'haw',
				'post_title'        => 'Hawaiian',
				'alternate_names'   => 'Hawaiian, ’Olelo Hawai’i',
				'standard_name'     => 'Hawaiian',
				'speakers_recorded' => 'a:3:{i:0;i:20206;i:1;i:20207;i:2;i:20208;}',
			),
			(object) array(
				'ID'                => 20,
				'post_name'         => 'bdh',
				'post_title'        => 'Baka',
				'alternate_names'   => null,
				'standard_name'     => 'Baka (South Sudan)',
				'speakers_recorded' => null,
			),
			(object) array(
				'ID'                => 40,
				'post_name'         => 'zzz',
				'post_title'        => 'Only A Title',
				'alternate_names'   => '',
				'standard_name'     => '',
				'speakers_recorded' => null,
			),
			(object) array(
				'ID'                => 50,
				'post_name'         => '',
				'post_title'        => 'No slug',
				'alternate_names'   => 'No slug',
				'standard_name'     => null,
				'speakers_recorded' => null,
			),
		);
	}

	public function test_rows_sorted_by_id_with_slug_as_identifier() {
		$rows = WT_Typeahead_Index::build_rows( $this->raw() );
		$this->assertSame( array( 10, 20, 30, 40 ), array_column( $rows, 'id' ) );
		$this->assertSame( array( 'haw', 'bdh', 'tah', 'zzz' ), array_column( $rows, 'identifier' ) );
	}

	public function test_label_falls_back_from_alternate_names_to_standard_name_to_title() {
		$labels = array_column( WT_Typeahead_Index::build_rows( $this->raw() ), 'label', 'identifier' );
		$this->assertSame( 'Hawaiian, ’Olelo Hawai’i', $labels['haw'] );
		$this->assertSame( 'Baka (South Sudan)', $labels['bdh'] );
		$this->assertSame( 'Only A Title', $labels['zzz'] );
	}

	public function test_resourced_means_at_least_one_oral_history() {
		$resourced = array_column( WT_Typeahead_Index::build_rows( $this->raw() ), 'resourced', 'identifier' );
		$this->assertTrue( $resourced['haw'] );
		$this->assertFalse( $resourced['tah'] );
		$this->assertFalse( $resourced['bdh'] );
	}

	public function test_rebuilding_unchanged_data_is_byte_identical() {
		$reversed = array_reverse( $this->raw() );
		$this->assertSame(
			WT_Typeahead_Index::encode( WT_Typeahead_Index::build_rows( $this->raw() ) ),
			WT_Typeahead_Index::encode( WT_Typeahead_Index::build_rows( $reversed ) )
		);
	}

	public function test_encoding_keeps_unicode_readable() {
		$json = WT_Typeahead_Index::encode( WT_Typeahead_Index::build_rows( $this->raw() ) );
		$this->assertStringContainsString( '’Olelo', $json );
	}

	public function test_diff_counts_added_removed_changed_and_second_run_is_zero() {
		$old = WT_Typeahead_Index::build_rows( $this->raw() );
		$raw = $this->raw();
		unset( $raw[0] ); // Tahitian removed.
		$raw[1]->alternate_names = 'Hawaiian, ’Olelo Hawai’i, Hawaiian Pidgin'; // Renamed.
		$raw[]                   = (object) array(
			'ID'                => 60,
			'post_name'         => 'cha',
			'post_title'        => 'Chamorro',
			'alternate_names'   => 'Chamorro, Chamoru',
			'standard_name'     => 'Chamorro',
			'speakers_recorded' => null,
		);
		$new                     = WT_Typeahead_Index::build_rows( $raw );

		$this->assertSame(
			array(
				'added'   => 1,
				'removed' => 1,
				'changed' => 1,
			),
			WT_Typeahead_Index::diff( $old, $new )
		);
		$this->assertSame(
			array(
				'added'   => 0,
				'removed' => 0,
				'changed' => 0,
			),
			WT_Typeahead_Index::diff( $new, $new )
		);
	}

	public function test_samples_are_first_names_and_deterministic() {
		$rows    = WT_Typeahead_Index::build_rows( $this->raw() );
		$samples = WT_Typeahead_Index::samples( $rows );
		$this->assertSame( array( 'Hawaiian', 'Baka (South Sudan)', 'Tahitian', 'Only A Title' ), $samples );
		$this->assertSame( $samples, WT_Typeahead_Index::samples( $rows ) );
		$this->assertSame( array(), WT_Typeahead_Index::samples( array() ) );
	}

	public function test_samples_are_capped() {
		$rows = array();
		for ( $i = 1; $i <= 8000; $i++ ) {
			$rows[] = array(
				'id'         => $i,
				'label'      => "Lang $i, Alt",
				'identifier' => "l$i",
				'resourced'  => false,
			);
		}
		$this->assertCount( WT_Typeahead_Index::SAMPLE_COUNT, WT_Typeahead_Index::samples( $rows ) );
	}

	public function test_a_batch_of_saves_schedules_one_rebuild() {
		$state = new ArrayObject( array( 'scheduled' => false ) );
		WP_Mock::userFunction( 'wp_next_scheduled' )->andReturnUsing(
			static function () use ( $state ) {
				return $state['scheduled'] ? 123 : false;
			}
		);
		WP_Mock::userFunction( 'wp_schedule_single_event' )->once()->andReturnUsing(
			static function () use ( $state ) {
				$state['scheduled'] = true;
				return true;
			}
		);
		for ( $i = 0; $i < 300; $i++ ) {
			WT_Typeahead_Index::schedule();
		}
		$this->assertConditionsMet();
	}
}
