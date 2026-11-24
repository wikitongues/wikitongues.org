<?php
use PHPUnit\Framework\TestCase;
use WT\Rights\Exporter;

class ExporterTest extends TestCase {

	private const DOC = 'https://docs.google.com/document/d/1TestlandTestlandTestlandTest/edit';

	/** A row as Exporter::row() makes it, matching RightsFixture::report()'s answers. */
	private function row( array $overrides = array() ): array {
		return array_merge(
			array(
				'record_id'   => 'recTestland00001',
				'name'        => 'Testland',
				'status'      => 'Completed',
				'region'      => 'South and Central Asia',
				'report_link' => self::DOC,
				'nation_id'   => 'recNationTest001',
				'answers'     => array(
					'i1'    => 'yes',
					'i2'    => 'unclear',
					'i3'    => 'no',
					'i4'    => 'yes',
					'i5'    => 'not applicable',
					'ante'  => 'positive',
					'trend' => 'negative',
				),
			),
			$overrides
		);
	}

	/**
	 * An exporter whose Docs are fixtures, counting fetches.
	 *
	 * @param array<string, mixed> $document The Doc every fetch returns.
	 */
	private function exporter( ?array $document = null, int &$fetches = 0 ): Exporter {
		$document = $document ?? RightsFixture::document( RightsFixture::report(), RightsFixture::wordpress_tab() );
		return new Exporter(
			wt_rights_structure(),
			function ( string $id ) use ( $document, &$fetches ): array {
				++$fetches;
				return $document;
			},
			fn( string $name ): string => strtolower( str_replace( ' ', '-', $name ) )
		);
	}

	private function build( array $rows, ?array $previous = null, array $options = array() ): array {
		return $this->exporter()->build(
			$rows,
			$previous,
			$options + array(
				'regions' => array( 'south-central-asia' ),
				'release' => '2027.1',
				'today'   => '2027-02-01',
			)
		);
	}

	public function test_a_completed_country_is_exported_at_1_0() {
		$result = $this->build( array( $this->row() ) );
		$report = $result['release']['reports'][0];

		$this->assertSame( 0, $result['errors'] );
		$this->assertSame( 'testland', $report['slug'] );
		$this->assertSame( '1.0', $report['version'] );
		$this->assertSame( '1TestlandTestlandTestlandTest', $report['doc_id'] );
		$this->assertSame( 'A new school opens', $report['content']['story_card']['title'] );
		$this->assertSame( array( 'recTestland00001' ), $result['release']['regions'][0]['reports'] );
	}

	public function test_rows_map_from_airtable_field_ids() {
		$fields = ( require __DIR__ . '/../../../wp-content/plugins/wt-rights/config/airtable.php' )['sandbox']['reports']['fields'];
		$row    = Exporter::row(
			array(
				'id'     => 'recX',
				'fields' => array(
					$fields['name']   => 'Testland',
					$fields['status'] => 'Completed',
					$fields['nation'] => array( 'recNation' ),
					$fields['i1']     => 'Yes',
					$fields['ante']   => array( 'name' => 'Positive' ),
				),
			),
			$fields
		);

		$this->assertSame( 'recNation', $row['nation_id'] );
		$this->assertSame( 'yes', $row['answers']['i1'] );
		$this->assertSame( 'positive', $row['answers']['ante'] );
		$this->assertSame( '', $row['answers']['i2'] );
	}

	public function test_held_and_unstarted_countries_are_forthcoming_and_not_fetched() {
		$fetches = 0;
		$result  = $this->exporter( null, $fetches )->build(
			array(
				$this->row(
					array(
						'status'    => 'Held',
						'record_id' => 'recHeld',
						'name'      => 'Holdland',
					)
				),
				$this->row(
					array(
						'status'    => 'Unitiated',
						'record_id' => 'recNew',
						'name'      => 'Newland',
					)
				),
			),
			null,
			array( 'regions' => array( 'south-central-asia' ) )
		);

		$this->assertSame( 0, $fetches );
		$this->assertSame( 0, $result['errors'] );
		$this->assertSame( array( 'Holdland', 'Newland' ), array_column( $result['release']['regions'][0]['forthcoming'], 'name' ) );
		$this->assertSame( array(), $result['release']['reports'] );
	}

	public function test_an_unfinished_country_blocks_the_region() {
		$result = $this->build( array( $this->row( array( 'status' => 'Pending review' ) ) ) );

		$this->assertSame( 1, $result['errors'] );
		$this->assertSame( array( 'region_blocked' ), RightsFixture::codes( $result['countries'][0]['problems'] ) );
	}

	public function test_preview_exports_unfinished_countries_as_drafts_with_warnings() {
		$result = $this->build(
			array(
				$this->row(
					array(
						'status'  => 'Pending review',
						'answers' => array( 'i3' => 'yes' ) + $this->row()['answers'],
					)
				),
			),
			null,
			array( 'preview' => true )
		);

		$this->assertSame( 0, $result['errors'] );
		$this->assertTrue( $result['release']['reports'][0]['draft'] );
		$this->assertSame( array( 'answer_mismatch' ), RightsFixture::codes( $result['countries'][0]['problems'] ) );
		$this->assertSame( 'warning', $result['countries'][0]['problems'][0]['severity'] );
	}

	public function test_every_region_is_listed_but_only_included_ones_carry_reports() {
		$fetches = 0;
		$result  = $this->exporter( null, $fetches )->build(
			array(
				$this->row(
					array(
						'region'    => 'Europe',
						'name'      => 'Eurland',
						'record_id' => 'recEur',
					)
				),
			),
			null,
			array( 'regions' => array( 'south-central-asia' ) )
		);
		$europe  = $result['release']['regions'][1];

		$this->assertSame( 0, $fetches );
		$this->assertSame( array(), $result['countries'] );
		$this->assertSame( array(), $result['release']['reports'] );
		$this->assertCount( 7, $result['release']['regions'] );
		$this->assertSame( array( true, false ), array( $result['release']['regions'][0]['included'], $europe['included'] ) );
		$this->assertSame( 'europe', $europe['key'] );
		$this->assertSame( array( 'Eurland' ), $europe['nations'] );
		$this->assertSame( array(), $europe['reports'] );
	}

	public function test_a_release_keeps_the_previous_releases_regions() {
		$first  = $this->build( array( $this->row() ) )['release'];
		$second = $this->build(
			array(
				$this->row(),
				$this->row(
					array(
						'record_id' => 'recEurope',
						'name'      => 'Eurland',
						'region'    => 'Europe',
					)
				),
			),
			$first,
			array(
				'regions' => array( 'europe' ),
				'release' => '2027.2',
			)
		);

		$included = array_column( array_filter( $second['release']['regions'], fn( array $r ): bool => $r['included'] ), 'key' );
		$this->assertSame( array( 'south-central-asia', 'europe' ), array_values( $included ) );
		$this->assertCount( 2, $second['release']['reports'] );
	}

	public function test_a_slug_is_kept_after_a_rename() {
		$first  = $this->build( array( $this->row( array( 'name' => 'Naoero' ) ) ) )['release'];
		$second = $this->build( array( $this->row( array( 'name' => 'Nauru' ) ) ), $first );

		$this->assertSame( 'naoero', $second['release']['reports'][0]['slug'] );
		$this->assertSame( 'Nauru', $second['release']['reports'][0]['name'] );
	}

	public function test_a_slug_that_clashes_with_a_region_is_an_error() {
		$result = $this->build( array( $this->row( array( 'name' => 'Europe' ) ) ) );

		$this->assertContains( 'slug_taken', RightsFixture::codes( $result['countries'][0]['problems'] ) );
	}

	public function test_an_unknown_region_is_an_error() {
		$result = $this->build( array( $this->row( array( 'region' => 'Antarctica' ) ) ) );

		$this->assertSame( array( 'unknown_region' ), RightsFixture::codes( $result['countries'][0]['problems'] ) );
	}

	public function test_a_doc_that_cannot_be_read_is_an_error_not_a_crash() {
		$exporter = new Exporter(
			wt_rights_structure(),
			function (): array {
				throw new RuntimeException( 'Google Docs returned HTTP 403: The caller does not have permission' );
			},
			fn( string $n ): string => strtolower( $n )
		);

		$result = $exporter->build( array( $this->row() ), null, array( 'regions' => array( 'south-central-asia' ) ) );

		$this->assertSame( array( 'doc_unreadable' ), RightsFixture::codes( $result['countries'][0]['problems'] ) );
		$this->assertStringContainsString( 'HTTP 403', $result['countries'][0]['problems'][0]['message'] );
	}

	public function test_a_published_change_needs_a_note_then_bumps_the_version() {
		$dates = array( 'release_dates' => array( 'south-central-asia' => '2027-02-19' ) );
		$first = $this->build( array( $this->row() ), null, $dates + array( 'today' => '2027-02-19' ) )['release'];
		$this->assertSame( '1.0', $first['reports'][0]['version'] );
		$this->assertSame( array( '1.0' ), array_column( $first['reports'][0]['changelog'], 'version' ) );

		$edited    = new Exporter(
			wt_rights_structure(),
			fn(): array => RightsFixture::document( RightsFixture::report( array( 'headline' => 'Rights have narrowed further.' ) ), RightsFixture::wordpress_tab() ),
			fn( string $n ): string => strtolower( $n )
		);
		$options   = $dates + array(
			'regions' => array(),
			'release' => '2027.1.1',
			'today'   => '2027-03-03',
		);
		$no_note   = $edited->build( array( $this->row() ), $first, $options );
		$with_note = $edited->build( array( $this->row() ), $first, $options + array( 'notes' => array( 'recTestland00001' => array( 'Reworded the headline.' ) ) ) );

		$this->assertSame( array( 'missing_change_note' ), RightsFixture::codes( $no_note['countries'][0]['problems'] ) );
		$this->assertSame( 0, $with_note['errors'] );
		$this->assertSame( '1.1', $with_note['release']['reports'][0]['version'] );
		$this->assertSame( array( '1.1', '1.0' ), array_column( $with_note['release']['reports'][0]['changelog'], 'version' ) );
		$this->assertSame( array( 'Reworded the headline.' ), $with_note['release']['reports'][0]['changelog'][0]['notes'] );
	}

	public function test_an_unchanged_rebuild_gives_the_same_versions_and_hashes() {
		$first  = $this->build( array( $this->row() ) )['release'];
		$second = $this->build( array( $this->row() ), $first )['release'];

		$this->assertSame( $first['reports'][0]['hash'], $second['reports'][0]['hash'] );
		$this->assertSame( $first['reports'][0]['version'], $second['reports'][0]['version'] );
	}

	public function test_release_dates_map_publishing_rows_to_region_keys() {
		$fields = array(
			'region'       => 'fldRegion',
			'release_date' => 'fldDate',
		);
		$dates  = $this->exporter()->release_dates(
			array(
				array(
					'id'     => 'rec1',
					'fields' => array(
						'fldRegion' => 'South and Central Asia',
						'fldDate'   => '2027-02-19',
					),
				),
				array(
					'id'     => 'rec2',
					'fields' => array(
						'fldRegion' => 'Middle East & North Africa',
						'fldDate'   => '2027-04-29',
					),
				),
				array(
					'id'     => 'rec3',
					'fields' => array( 'fldRegion' => 'Europe' ),
				),
			),
			$fields
		);

		$this->assertSame(
			array(
				'south-central-asia'       => '2027-02-19',
				'middle-east-north-africa' => '2027-04-29',
			),
			$dates
		);
	}

	public function test_notes_since_the_previous_release_are_grouped_by_report_oldest_first() {
		$fields  = array(
			'note'   => 'fldNote',
			'report' => 'fldReport',
			'date'   => 'fldDate',
		);
		$change  = fn( string $note, string $date, string $report ): array => array(
			'id'     => 'rec' . $date,
			'fields' => array(
				'fldNote'   => $note,
				'fldDate'   => $date,
				'fldReport' => array( $report ),
			),
		);
		$records = array(
			$change( 'Later fix.', '2027-03-10', 'recA' ),
			$change( 'Old fix.', '2027-02-01', 'recA' ),
			$change( 'First fix.', '2027-03-03', 'recA' ),
			$change( 'Other country.', '2027-03-04', 'recB' ),
		);

		$notes = Exporter::notes_since( $records, $fields, array( 'built_at' => '2027-03-01T10:00:00+00:00' ) );

		$this->assertSame(
			array(
				'recA' => array( 'First fix.', 'Later fix.' ),
				'recB' => array( 'Other country.' ),
			),
			$notes
		);
		$this->assertCount( 3, Exporter::notes_since( $records, $fields, null )['recA'] );
	}

	public function test_every_base_maps_the_same_tables_and_fields() {
		$bases = require __DIR__ . '/../../../wp-content/plugins/wt-rights/config/airtable.php';
		$shape = static fn( array $base ): array => array_map( static fn( array $table ): array => array_keys( $table['fields'] ), array_diff_key( $base, array( 'base' => true ) ) );
		$this->assertSame( $shape( $bases['sandbox'] ), $shape( $bases['production'] ) );
	}
}
