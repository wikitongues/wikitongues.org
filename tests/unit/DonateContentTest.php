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

	// wt_donate_stat_display().

	public function test_custom_stat_shows_typed_text() {
		$stat = array(
			'stat_type'  => 'custom',
			'stat_value' => ' 34 ',
			'stat'       => 'fellows',
		);
		$this->assertSame( '34', wt_donate_stat_display( $stat ) );
	}

	public function test_live_stat_shows_formatted_count() {
		WP_Mock::userFunction(
			'get_transient',
			array(
				'return' => array(
					'fellow_count'  => 1065,
					'country_count' => 1,
				),
			)
		);
		WP_Mock::userFunction( 'number_format_i18n', array( 'return' => '1,065' ) );
		$stat = array(
			'stat_type'  => 'live',
			'stat'       => 'fellows',
			'stat_value' => 'ignored',
		);
		$this->assertSame( '1,065', wt_donate_stat_display( $stat ) );
	}

	// wt_count_distinct_territories().

	public function test_territory_count_dedupes_across_fellows() {
		$raw = array( array( '10' ), array( '10', '11' ), '12', array() );
		$this->assertSame( 3, wt_count_distinct_territories( $raw, array( 10, 11, 12 ) ) );
	}

	public function test_territory_count_ignores_unpublished_territories() {
		$raw = array( array( '10', '99' ), '' );
		$this->assertSame( 1, wt_count_distinct_territories( $raw, array( 10 ) ) );
	}

	public function test_fellow_countries_stat_reads_cached_stats() {
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'wt_fellow_stats' ),
				'return' => array(
					'fellow_count'  => 65,
					'country_count' => 34,
				),
			)
		);
		$this->assertSame( 34, wt_donate_stat( 'fellow_countries' ) );
		$this->assertSame( 65, wt_donate_stat( 'fellows' ) );
	}

	// wt_acf_value_by_key().

	private function flexible_field() {
		return array(
			'type'    => 'flexible_content',
			'layouts' => array(
				'layout_a' => array(
					'name'       => 'hero',
					'sub_fields' => array(
						array(
							'name' => 'header',
							'key'  => 'field_header',
							'type' => 'text',
						),
						array(
							'name'       => 'box',
							'key'        => 'field_box',
							'type'       => 'group',
							'sub_fields' => array(
								array(
									'name' => 'note',
									'key'  => 'field_note',
									'type' => 'text',
								),
							),
						),
						array(
							'name'       => 'items',
							'key'        => 'field_items',
							'type'       => 'repeater',
							'sub_fields' => array(
								array(
									'name' => 'title',
									'key'  => 'field_title',
									'type' => 'text',
								),
							),
						),
					),
				),
			),
		);
	}

	public function test_value_by_key_rekeys_nested_layouts() {
		$value = array(
			array(
				'acf_fc_layout' => 'hero',
				'header'        => 'Hi',
				'box'           => array( 'note' => 'N' ),
				'items'         => array( array( 'title' => 'One' ), array( 'title' => 'Two' ) ),
			),
		);
		$this->assertSame(
			array(
				array(
					'acf_fc_layout' => 'hero',
					'field_header'  => 'Hi',
					'field_box'     => array( 'field_note' => 'N' ),
					'field_items'   => array( array( 'field_title' => 'One' ), array( 'field_title' => 'Two' ) ),
				),
			),
			wt_acf_value_by_key( $value, $this->flexible_field() )
		);
	}

	public function test_value_by_key_drops_unknown_layouts_and_fields() {
		$value = array(
			array( 'acf_fc_layout' => 'missing' ),
			array(
				'acf_fc_layout' => 'hero',
				'stray'         => 'x',
			),
		);
		$this->assertSame(
			array( array( 'acf_fc_layout' => 'hero' ) ),
			wt_acf_value_by_key( $value, $this->flexible_field() )
		);
	}

	// wt_donate_default_value().

	public function test_default_value_ignores_other_fields() {
		$this->assertSame( 'x', wt_donate_default_value( 'x', 16670, array( 'key' => 'field_other' ) ) );
	}

	public function test_default_value_ignores_other_templates() {
		WP_Mock::userFunction( 'get_page_template_slug', array( 'return' => 'template-editorial.php' ) );
		$this->assertFalse( wt_donate_default_value( false, 2, array( 'key' => WT_MAIN_CONTENT_KEY ) ) );
	}

	public function test_saved_blocks_win() {
		WP_Mock::userFunction( 'get_page_template_slug', array( 'return' => 'template-donate.php' ) );
		WP_Mock::userFunction( 'metadata_exists', array( 'return' => true ) );
		$this->assertSame( array( 'saved' ), wt_donate_default_value( array( 'saved' ), 16670, array( 'key' => WT_MAIN_CONTENT_KEY ) ) );
	}
}
