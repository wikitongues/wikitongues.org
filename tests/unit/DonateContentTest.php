<?php
use WP_Mock\Tools\TestCase;

class DonateContentTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction( 'esc_html', array( 'return_arg' => 0 ) );
		WP_Mock::userFunction( 'esc_attr', array( 'return_arg' => 0 ) );
	}

	// wt_donate_marked().

	public function test_marked_wraps_asterisk_text_in_span() {
		$this->assertSame(
			'Every language <span class="hl">in the world.</span>',
			wt_donate_marked( 'Every language *in the world.*', 'hl' )
		);
	}

	public function test_marked_leaves_unmarked_text_alone() {
		$this->assertSame( 'Plain text', wt_donate_marked( 'Plain text', 'hl' ) );
	}

	public function test_marked_ignores_a_lone_asterisk() {
		$this->assertSame( 'Gifts* are welcome', wt_donate_marked( 'Gifts* are welcome', 'hl' ) );
	}

	// wt_donate_amounts().

	public function test_amounts_parses_three_values() {
		$this->assertSame( array( 50, 100, 250 ), wt_donate_amounts( '50, 100, 250', array( 1, 2, 3 ) ) );
	}

	public function test_amounts_strips_dollar_signs() {
		$this->assertSame( array( 5, 10, 25 ), wt_donate_amounts( '$5,$10, $25', array( 1, 2, 3 ) ) );
	}

	public function test_amounts_falls_back_unless_exactly_three() {
		$this->assertSame( array( 1, 2, 3 ), wt_donate_amounts( '50, 100', array( 1, 2, 3 ) ) );
		$this->assertSame( array( 1, 2, 3 ), wt_donate_amounts( '5, 10, 25, 50', array( 1, 2, 3 ) ) );
		$this->assertSame( array( 1, 2, 3 ), wt_donate_amounts( '', array( 1, 2, 3 ) ) );
	}

	// wt_donate_default_value().

	public function test_default_fills_unsaved_text_field() {
		WP_Mock::userFunction( 'metadata_exists', array( 'return' => false ) );
		$field = array( 'name' => 'donate_gift_title' );
		$this->assertSame( 'Make a gift', wt_donate_default_value( null, 16670, $field ) );
	}

	public function test_saved_value_wins_even_when_empty() {
		WP_Mock::userFunction( 'metadata_exists', array( 'return' => true ) );
		$field = array( 'name' => 'donate_gift_title' );
		$this->assertSame( '', wt_donate_default_value( '', 16670, $field ) );
	}

	public function test_other_fields_are_untouched() {
		$field = array( 'name' => 'banner' );
		$this->assertSame( 'x', wt_donate_default_value( 'x', 16670, $field ) );
	}

	public function test_repeater_default_rows_are_keyed_by_sub_field_key() {
		WP_Mock::userFunction( 'metadata_exists', array( 'return' => false ) );
		$field = array(
			'name'       => 'donate_programs',
			'sub_fields' => array(
				array(
					'name' => 'title',
					'key'  => 'field_t',
				),
				array(
					'name' => 'text',
					'key'  => 'field_x',
				),
			),
		);
		$rows  = wt_donate_default_value( false, 16670, $field );
		$this->assertCount( 3, $rows );
		$this->assertSame( 'Language Revitalization Fellowship', $rows[0]['field_t'] );
		$this->assertArrayHasKey( 'field_x', $rows[0] );
	}
}
