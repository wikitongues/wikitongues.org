<?php
use WP_Mock\Tools\TestCase;

class ResourceCardAbilitiesTest extends TestCase {

	private const PAGE_ID = 15057;

	/**
	 * A card as ACF returns it (formatted).
	 *
	 * @return array<string, mixed>
	 */
	private function card( string $header, string $url, string $link_type = 'download' ): array {
		return array(
			'header'    => $header,
			'text'      => $header . ' text',
			'link_type' => $link_type,
			'link'      => array(
				'url'    => $url,
				'title'  => 'Learn more',
				'target' => '',
			),
		);
	}

	/**
	 * A `main_content` value shaped like the Resources page: an intro text
	 * layout, then the Card block.
	 *
	 * @param array<int, array<string, mixed>> $cards Cards in the Card block.
	 * @return array<int, array<string, mixed>>
	 */
	private function content( array $cards ): array {
		return array(
			array(
				'acf_fc_layout' => 'text_layout',
				'text'          => 'These free resources can help you.',
			),
			array(
				'acf_fc_layout' => 'block_layout',
				'block_type'    => 'Card',
				'block_style'   => 'light',
				'block_group'   => $cards,
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function valid_input(): array {
		return array(
			'header' => 'Wikitongues AI',
			'text'   => 'Follow Igala speakers as they test how well AI models speak their language.',
			'url'    => 'https://wikitongues-ai-site.vercel.app/',
		);
	}

	private function mock_page_found(): void {
		WP_Mock::userFunction(
			'get_page_by_path',
			array(
				'args'   => array( 'revitalization/resources' ),
				'return' => (object) array( 'ID' => self::PAGE_ID ),
			)
		);
	}

	// --- wt_resource_cards_block_position -----------------------------------

	public function test_block_position_is_one_based_row_of_first_card_block() {
		$this->assertSame( 2, wt_resource_cards_block_position( $this->content( array() ) ) );
	}

	public function test_block_position_skips_wide_block_layouts() {
		$content = array(
			array(
				'acf_fc_layout' => 'block_layout',
				'block_type'    => 'Block',
				'block_group'   => array(),
			),
			array(
				'acf_fc_layout' => 'block_layout',
				'block_type'    => 'Card',
				'block_group'   => array(),
			),
		);
		$this->assertSame( 2, wt_resource_cards_block_position( $content ) );
	}

	public function test_block_position_is_null_without_a_card_block() {
		$this->assertNull( wt_resource_cards_block_position( array( array( 'acf_fc_layout' => 'text_layout' ) ) ) );
		$this->assertNull( wt_resource_cards_block_position( false ) );
		$this->assertNull( wt_resource_cards_block_position( null ) );
	}

	// --- wt_resource_cards_from_content -------------------------------------

	public function test_cards_are_flattened_to_strings() {
		$cards = wt_resource_cards_from_content(
			$this->content( array( $this->card( 'Toolkit', 'https://wikitongues.org/documents/toolkit/' ) ) )
		);
		$this->assertSame(
			array(
				array(
					'header'    => 'Toolkit',
					'text'      => 'Toolkit text',
					'link_type' => 'download',
					'url'       => 'https://wikitongues.org/documents/toolkit/',
					'link_text' => 'Learn more',
				),
			),
			$cards
		);
	}

	public function test_card_without_link_has_empty_url() {
		$cards = wt_resource_cards_from_content( $this->content( array( array( 'header' => 'Bare' ) ) ) );
		$this->assertSame( '', $cards[0]['url'] );
		$this->assertSame( '', $cards[0]['text'] );
	}

	public function test_no_cards_when_no_card_block() {
		$this->assertSame( array(), wt_resource_cards_from_content( array() ) );
	}

	// --- wt_resource_cards_has_url ------------------------------------------

	public function test_has_url_ignores_trailing_slash_and_case() {
		$cards = wt_resource_cards_from_content(
			$this->content( array( $this->card( 'AI', 'https://Wikitongues-AI-Site.vercel.app' ) ) )
		);
		$this->assertTrue( wt_resource_cards_has_url( $cards, 'https://wikitongues-ai-site.vercel.app/' ) );
		$this->assertFalse( wt_resource_cards_has_url( $cards, 'https://app.wikitongues.org/' ) );
	}

	public function test_empty_card_urls_never_match() {
		$cards = wt_resource_cards_from_content( $this->content( array( array( 'header' => 'Bare' ) ) ) );
		$this->assertFalse( wt_resource_cards_has_url( $cards, '' ) );
	}

	// --- wt_resource_card_clean_input ---------------------------------------

	public function test_clean_input_trims_and_defaults_link_text() {
		$clean = wt_resource_card_clean_input(
			array(
				'header' => '  Wikitongues AI ',
				'text'   => ' Some text. ',
				'url'    => ' https://wikitongues-ai-site.vercel.app/ ',
			)
		);
		$this->assertSame(
			array(
				'header'       => 'Wikitongues AI',
				'text'         => 'Some text.',
				'url'          => 'https://wikitongues-ai-site.vercel.app/',
				'link_text'    => 'Learn more',
				'thumbnail_id' => 0,
			),
			$clean
		);
	}

	public function test_clean_input_requires_header_and_text() {
		$input           = $this->valid_input();
		$input['header'] = '   ';
		$this->assertSame( 'wt_resource_card_missing', wt_resource_card_clean_input( $input )->get_error_code() );
		$this->assertSame( 'wt_resource_card_missing', wt_resource_card_clean_input( null )->get_error_code() );
	}

	public function test_clean_input_rejects_non_https_urls() {
		foreach ( array( 'http://wikitongues.org/', 'javascript:alert(1)', 'not a url', '/relative/path', '' ) as $url ) {
			$input        = $this->valid_input();
			$input['url'] = $url;
			$result       = wt_resource_card_clean_input( $input );
			$this->assertInstanceOf( WP_Error::class, $result, $url );
			$this->assertSame( 'wt_resource_card_url', $result->get_error_code(), $url );
		}
	}

	public function test_clean_input_enforces_lengths() {
		$input         = $this->valid_input();
		$input['text'] = str_repeat( 'a', 301 );
		$this->assertSame( 'wt_resource_card_too_long', wt_resource_card_clean_input( $input )->get_error_code() );
	}

	// --- wt_resource_card_row -----------------------------------------------

	public function test_row_is_a_navigation_link_card_keyed_by_field_key() {
		$row = wt_resource_card_row( wt_resource_card_clean_input( $this->valid_input() ) );
		$this->assertSame( 'Wikitongues AI', $row['field_67b738f42921e'] );
		$this->assertSame( 'link', $row['field_67b744c6c4a2c'] );
		$this->assertSame( 'No', $row['field_67d9fc06dd981'] );
		$this->assertSame( 'No', $row['field_67da8def62ea0'] );
		$this->assertSame( '', $row['field_67b738d82921d'] );
		$this->assertSame(
			array(
				'title'  => 'Learn more',
				'url'    => 'https://wikitongues-ai-site.vercel.app/',
				'target' => '',
			),
			$row['field_67b745bf6f3df']
		);
	}

	public function test_row_carries_thumbnail_id_when_given() {
		$input                 = $this->valid_input();
		$input['thumbnail_id'] = 321;
		$row                   = wt_resource_card_row( wt_resource_card_clean_input( $input ) );
		$this->assertSame( 321, $row['field_67b738d82921d'] );
	}

	// --- wt_resource_cards_execute_add --------------------------------------

	public function test_add_writes_one_row_to_the_card_block_and_reads_it_back() {
		$this->mock_page_found();
		$existing = array( $this->card( 'Toolkit', 'https://wikitongues.org/documents/toolkit/' ) );
		$after    = array_merge( $existing, array( $this->card( 'Wikitongues AI', 'https://wikitongues-ai-site.vercel.app/', 'link' ) ) );

		WP_Mock::userFunction(
			'get_field',
			array(
				'args'            => array( 'main_content', self::PAGE_ID ),
				'return_in_order' => array( $this->content( $existing ), $this->content( $after ) ),
			)
		);
		WP_Mock::userFunction(
			'add_sub_row',
			array(
				'times'  => 1,
				'args'   => array(
					array( 'field_679e98a9c94e7', 2, 'field_67d69cc1ec928' ),
					WP_Mock\Functions::type( 'array' ),
					self::PAGE_ID,
				),
				'return' => 2,
			)
		);
		WP_Mock::userFunction(
			'acf_flush_value_cache',
			array(
				'times' => 1,
				'args'  => array( self::PAGE_ID, 'main_content' ),
			)
		);
		WP_Mock::userFunction( 'get_permalink', array( 'return' => 'https://wikitongues.org/revitalization/resources/' ) );

		$result = wt_resource_cards_execute_add( $this->valid_input() );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['added'] );
		$this->assertSame( 'https://wikitongues.org/revitalization/resources/', $result['page_url'] );
		$this->assertCount( 2, $result['cards'] );
		$this->assertSame( 'Wikitongues AI', $result['cards'][1]['header'] );
	}

	public function test_add_refuses_a_url_already_on_the_page_without_writing() {
		$this->mock_page_found();
		WP_Mock::userFunction(
			'get_field',
			array( 'return' => $this->content( array( $this->card( 'AI', 'https://wikitongues-ai-site.vercel.app' ) ) ) )
		);
		WP_Mock::userFunction( 'add_sub_row', array( 'times' => 0 ) );

		$result = wt_resource_cards_execute_add( $this->valid_input() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'wt_resource_card_exists', $result->get_error_code() );
	}

	public function test_add_refuses_when_page_is_missing() {
		WP_Mock::userFunction( 'get_page_by_path', array( 'return' => null ) );
		WP_Mock::userFunction( 'add_sub_row', array( 'times' => 0 ) );

		$result = wt_resource_cards_execute_add( $this->valid_input() );

		$this->assertSame( 'wt_resource_page_missing', $result->get_error_code() );
	}

	public function test_add_refuses_when_page_has_no_card_block() {
		$this->mock_page_found();
		WP_Mock::userFunction( 'get_field', array( 'return' => array( array( 'acf_fc_layout' => 'text_layout' ) ) ) );
		WP_Mock::userFunction( 'add_sub_row', array( 'times' => 0 ) );

		$result = wt_resource_cards_execute_add( $this->valid_input() );

		$this->assertSame( 'wt_resource_cards_block_missing', $result->get_error_code() );
	}

	public function test_add_rejects_bad_input_before_touching_the_page() {
		WP_Mock::userFunction( 'get_page_by_path', array( 'times' => 0 ) );
		WP_Mock::userFunction( 'add_sub_row', array( 'times' => 0 ) );
		$input        = $this->valid_input();
		$input['url'] = 'http://insecure.example/';

		$this->assertSame( 'wt_resource_card_url', wt_resource_cards_execute_add( $input )->get_error_code() );
	}

	public function test_add_rejects_a_thumbnail_that_is_not_an_image() {
		$this->mock_page_found();
		WP_Mock::userFunction(
			'wp_attachment_is_image',
			array(
				'args'   => array( 99 ),
				'return' => false,
			)
		);
		WP_Mock::userFunction( 'add_sub_row', array( 'times' => 0 ) );
		$input                 = $this->valid_input();
		$input['thumbnail_id'] = 99;

		$this->assertSame( 'wt_resource_card_thumbnail', wt_resource_cards_execute_add( $input )->get_error_code() );
	}

	public function test_add_reports_failure_when_card_does_not_read_back() {
		$this->mock_page_found();
		$existing = array( $this->card( 'Toolkit', 'https://wikitongues.org/documents/toolkit/' ) );
		WP_Mock::userFunction( 'get_field', array( 'return' => $this->content( $existing ) ) );
		WP_Mock::userFunction( 'add_sub_row', array( 'return' => 2 ) );
		WP_Mock::userFunction( 'acf_flush_value_cache' );

		$result = wt_resource_cards_execute_add( $this->valid_input() );

		$this->assertSame( 'wt_resource_card_not_saved', $result->get_error_code() );
	}

	// --- permissions --------------------------------------------------------

	public function test_can_edit_checks_the_resources_page_itself() {
		$this->mock_page_found();
		WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'edit_page', self::PAGE_ID ),
				'return' => true,
			)
		);
		$this->assertTrue( wt_resource_cards_can_edit() );
	}

	public function test_cannot_edit_when_page_is_missing() {
		WP_Mock::userFunction( 'get_page_by_path', array( 'return' => null ) );
		WP_Mock::userFunction( 'current_user_can', array( 'times' => 0 ) );
		$this->assertFalse( wt_resource_cards_can_edit() );
	}
}
