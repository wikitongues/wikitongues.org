<?php
use WP_Mock\Tools\TestCase;
use WT\AirtableSync\Field_Resolver;
use WT\AirtableSync\Record_Rows;

class RecordRowsTest extends TestCase {

	private const COLUMNS = array(
		'airtable_id' => 'subdivisions',
		'identifier'  => 'subdivision_identifiers',
		'name'        => 'subdivision_names',
	);

	public function test_zips_parallel_arrays_by_position() {
		$rows = Record_Rows::build(
			self::COLUMNS,
			array(
				'subdivisions'            => array( 'recAAAAAAAAAAAAAA', 'recBBBBBBBBBBBBBB' ),
				'subdivision_identifiers' => array( 'Guam_US', 'Northern-Mariana-Islands_US' ),
				'subdivision_names'       => array( 'Guam', 'Northern Mariana Islands' ),
			)
		);

		$this->assertSame(
			array(
				array(
					'airtable_id' => 'recAAAAAAAAAAAAAA',
					'identifier'  => 'Guam_US',
					'name'        => 'Guam',
				),
				array(
					'airtable_id' => 'recBBBBBBBBBBBBBB',
					'identifier'  => 'Northern-Mariana-Islands_US',
					'name'        => 'Northern Mariana Islands',
				),
			),
			$rows
		);
	}

	public function test_absent_key_column_leaves_field_alone() {
		$this->assertNull( Record_Rows::build( self::COLUMNS, array( 'subdivision_names' => array( 'Guam' ) ) ) );
	}

	public function test_empty_key_column_clears_rows() {
		$this->assertSame( array(), Record_Rows::build( self::COLUMNS, array( 'subdivisions' => '' ) ) );
		$this->assertSame( array(), Record_Rows::build( self::COLUMNS, array( 'subdivisions' => array() ) ) );
	}

	public function test_comma_separated_ids_are_split() {
		$rows = Record_Rows::build(
			array( 'airtable_id' => 'subdivisions' ),
			array( 'subdivisions' => 'recAAAAAAAAAAAAAA, recBBBBBBBBBBBBBB' )
		);

		$this->assertSame( array( 'recAAAAAAAAAAAAAA', 'recBBBBBBBBBBBBBB' ), array_column( $rows, 'airtable_id' ) );
	}

	public function test_label_string_with_commas_stays_one_label() {
		$rows = Record_Rows::build(
			self::COLUMNS,
			array(
				'subdivisions'            => array( 'recAAAAAAAAAAAAAA' ),
				'subdivision_identifiers' => 'Washington,-D.C._US',
			)
		);

		$this->assertSame( 'Washington,-D.C._US', $rows[0]['identifier'] );
		$this->assertSame( '', $rows[0]['name'] );
	}

	public function test_drops_non_ids_and_repeats_keeping_label_positions() {
		$rows = Record_Rows::build(
			self::COLUMNS,
			array(
				'subdivisions'            => array( 'Hawaii_US', 'recAAAAAAAAAAAAAA', 'recAAAAAAAAAAAAAA' ),
				'subdivision_identifiers' => array( 'x', 'Hawaii_US', 'Hawaii_US' ),
			)
		);

		$this->assertCount( 1, $rows );
		$this->assertSame( 'Hawaii_US', $rows[0]['identifier'] );
	}

	public function test_same_payload_gives_same_rows() {
		$payload = array(
			'subdivisions'      => array( 'recAAAAAAAAAAAAAA' ),
			'subdivision_names' => array( "Hawai\xc2\xa0i " ),
		);

		$this->assertSame( Record_Rows::build( self::COLUMNS, $payload ), Record_Rows::build( self::COLUMNS, $payload ) );
		$this->assertSame( 'Hawai i', Record_Rows::build( self::COLUMNS, $payload )[0]['name'] );
	}

	public function test_record_id_shape() {
		$this->assertTrue( Field_Resolver::is_record_id( 'rectHvWful079aST6' ) );
		$this->assertFalse( Field_Resolver::is_record_id( 'Greece' ) );
		$this->assertFalse( Field_Resolver::is_record_id( 'recShort' ) );
		$this->assertFalse( Field_Resolver::is_record_id( ' rectHvWful079aST6' ) );
	}
}
