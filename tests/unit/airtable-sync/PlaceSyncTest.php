<?php
use WP_Mock\Tools\TestCase;
use WT\AirtableSync\Field_Resolver;
use WT\AirtableSync\Place_Sync;

class PlaceSyncTest extends TestCase {

	public function test_iso_coded_record_is_a_territory() {
		$this->assertSame( 'territories', Place_Sync::target_type( 'GU' ) );
	}

	public function test_record_without_code_is_a_subdivision() {
		$this->assertSame( 'subdivisions', Place_Sync::target_type( '' ) );
		$this->assertSame( 'subdivisions', Place_Sync::target_type( '  ' ) );
	}

	public function test_new_territory_waits_as_draft_for_its_region() {
		$this->assertSame( 'draft', Place_Sync::new_post_status( 'territories' ) );
		$this->assertSame( 'publish', Place_Sync::new_post_status( 'subdivisions' ) );
	}

	public function test_place_without_record_id_is_rejected() {
		$result = ( new Place_Sync() )->sync( array( 'post_title' => 'Guam' ), array() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'wt_sync_missing_record_id', $result->get_error_code() );
	}

	public function test_place_keyed_on_a_name_is_rejected() {
		$result = ( new Place_Sync() )->sync( array( 'airtable_id' => 'Guam_US' ), array() );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_record_ids_keeps_only_ids_in_order_without_repeats() {
		$this->assertSame(
			array( 'recguDPgV3cIXLO34', 'rec8BH6e7SD1A1J5I' ),
			Field_Resolver::record_ids( array( 'recguDPgV3cIXLO34', 'Guam', 'rec8BH6e7SD1A1J5I', 'recguDPgV3cIXLO34' ) )
		);
	}

	public function test_record_ids_splits_comma_strings_and_strips_nbsp() {
		$this->assertSame(
			array( 'recguDPgV3cIXLO34', 'rec8BH6e7SD1A1J5I' ),
			Field_Resolver::record_ids( "recguDPgV3cIXLO34,\xc2\xa0rec8BH6e7SD1A1J5I " )
		);
	}

	public function test_record_ids_of_empty_value_is_empty() {
		$this->assertSame( array(), Field_Resolver::record_ids( '' ) );
		$this->assertSame( array(), Field_Resolver::record_ids( array() ) );
	}

	public function test_record_id_shape() {
		$this->assertTrue( Field_Resolver::is_record_id( 'rectHvWful079aST6' ) );
		$this->assertFalse( Field_Resolver::is_record_id( 'Greece' ) );
		$this->assertFalse( Field_Resolver::is_record_id( 'recShort' ) );
		$this->assertFalse( Field_Resolver::is_record_id( ' rectHvWful079aST6' ) );
	}
}
