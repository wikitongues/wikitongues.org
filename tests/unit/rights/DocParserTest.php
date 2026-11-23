<?php
use PHPUnit\Framework\TestCase;
use WT\Rights\Doc_Parser;

class DocParserTest extends TestCase {

	public function test_a_complete_doc_parses_without_problems() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report(), RightsFixture::wordpress_tab() ) );

		$this->assertSame( array(), $report['problems'] );
		$this->assertSame( 'Testland', $report['country'] );
		$this->assertSame( array( 'inventory', 'history', 'politics', 'testimonies' ), array_keys( $report['sections'] ) );
		$this->assertSame(
			array(
				'i1' => 'yes',
				'i2' => 'unclear',
				'i3' => 'no',
				'i4' => 'yes',
				'i5' => 'not applicable',
			),
			array_map( fn( $d ) => $d['answer'], $report['dimensions'] )
		);
		$this->assertSame( 'positive', $report['rating']['ante'] );
		$this->assertSame( 'negative', $report['rating']['trend'] );
		$this->assertSame( 'Testland’s language rights have narrowed since 2020.', $report['rating']['headline'] );
	}

	public function test_sources_are_separated_from_the_text() {
		$report  = RightsFixture::parse( RightsFixture::document( RightsFixture::report() ) );
		$history = $report['sections']['history'];

		$this->assertSame( "<p>Testlandic became official in 1901.</p>\n", $history['html'] );
		$this->assertSame( array( 'https://example.org/history-a', 'https://example.org/history-b' ), array_column( $history['sources'], 'url' ) );
		$this->assertSame( "<p>Evidence for i3.</p>\n", $report['dimensions']['i3']['html'] );
		$this->assertSame( 'https://example.org/i3', $report['dimensions']['i3']['sources'][0]['url'] );
	}

	public function test_the_story_card_comes_from_the_wordpress_tab() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report(), RightsFixture::wordpress_tab() ) );
		$card   = $report['story_card'];

		$this->assertSame( 'A new school opens', $card['title'] );
		$this->assertSame( "<p>Schools in the valley now teach Testlandic.</p>\n", $card['html'] );
		$this->assertSame(
			array(
				array(
					'url'   => 'https://example.org/school',
					'label' => 'School news',
				),
				array(
					'url'   => 'https://example.org/ministry',
					'label' => 'Ministry record',
				),
			),
			$card['links']
		);
	}

	public function test_no_wordpress_tab_means_no_story_card() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report() ) );

		$this->assertNull( $report['story_card'] );
		$this->assertSame( array(), $report['problems'] );
	}

	public function test_a_long_story_card_is_a_warning() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report(), RightsFixture::wordpress_tab( str_repeat( 'word ', 61 ) ) ) );

		$this->assertSame( array( 'story_card_long' ), RightsFixture::codes( $report['problems'] ) );
		$this->assertSame( 'warning', $report['problems'][0]['severity'] );
	}

	public function test_answers_are_read_however_they_are_typed() {
		$report = RightsFixture::parse(
			RightsFixture::document(
				RightsFixture::report(
					array(
						'answers' => array(
							'i1' => 'Yes.',
							'i2' => ' UNCLEAR ',
							'i5' => 'N/A',
						),
						'ante'    => 'Postitive',
					)
				)
			)
		);

		$this->assertSame( array(), $report['problems'] );
		$this->assertSame( 'yes', $report['dimensions']['i1']['answer'] );
		$this->assertSame( 'unclear', $report['dimensions']['i2']['answer'] );
		$this->assertSame( 'not applicable', $report['dimensions']['i5']['answer'] );
		$this->assertSame( 'positive', $report['rating']['ante'] );
	}

	public function test_missing_answers_are_errors_and_the_rest_still_parses() {
		$report = RightsFixture::parse(
			RightsFixture::document(
				RightsFixture::report(
					array(
						'answers' => array(
							'i2' => null,
							'i4' => null,
						),
					)
				)
			)
		);

		$this->assertSame( array( 'missing_answer', 'missing_answer' ), RightsFixture::codes( $report['problems'] ) );
		$this->assertSame( '', $report['dimensions']['i2']['answer'] );
		$this->assertSame( 'yes', $report['dimensions']['i1']['answer'] );
	}

	public function test_an_answer_outside_the_allowed_set_is_an_error() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report( array( 'answers' => array( 'i1' => 'Yes/No/Unclear' ) ) ) ) );

		$this->assertSame( array( 'invalid_answer' ), RightsFixture::codes( $report['problems'] ) );
	}

	public function test_not_applicable_is_only_allowed_on_indicator_5() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report( array( 'answers' => array( 'i1' => 'Not applicable' ) ) ) ) );

		$this->assertSame( array( 'invalid_answer' ), RightsFixture::codes( $report['problems'] ) );
	}

	public function test_older_question_wording_is_accepted() {
		$report = RightsFixture::parse(
			RightsFixture::document(
				RightsFixture::report( array( 'questions' => array( 'i1' => 'Has the country prohibited or significantly hindered the use of minority languages?' ) ) )
			)
		);

		$this->assertSame( array(), $report['problems'] );
		$this->assertSame( 'yes', $report['dimensions']['i1']['answer'] );
	}

	public function test_a_missing_question_is_an_error() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report( array( 'questions' => array( 'i3' => null ) ) ) ) );

		$this->assertSame( array( 'missing_question' ), RightsFixture::codes( $report['problems'] ) );
		$this->assertArrayNotHasKey( 'i3', $report['dimensions'] );
	}

	public function test_a_misnamed_heading_is_unknown_and_its_section_missing() {
		$elements = RightsFixture::report();
		foreach ( $elements as $i => $element ) {
			if ( 'Historical context' === rtrim( $element['paragraph']['elements'][0]['textRun']['content'] ) ) {
				$elements[ $i ] = RightsFixture::p( 'HEADING_1', 'History' );
			}
		}

		$report = RightsFixture::parse( RightsFixture::document( $elements ) );

		$this->assertSame( array( 'unknown_section', 'missing_section' ), RightsFixture::codes( $report['problems'] ) );
	}

	public function test_empty_testimonies_are_fine_but_an_empty_required_section_is_not() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report( array( 'testimonies' => false ) ) ) );
		$this->assertSame( array(), $report['problems'] );
		$this->assertSame( '', $report['sections']['testimonies']['html'] );

		$elements = array_values(
			array_filter(
				RightsFixture::report(),
				fn( $e ) => 'Testlandic became official in 1901.' !== rtrim( $e['paragraph']['elements'][0]['textRun']['content'] )
					&& 'https://example.org/history-a' !== $e['paragraph']['elements'][0]['textRun']['content']
			)
		);
		$this->assertSame( array( 'empty_section' ), RightsFixture::codes( RightsFixture::parse( RightsFixture::document( $elements ) )['problems'] ) );
	}

	public function test_a_rating_answer_on_the_line_after_its_question() {
		$elements = RightsFixture::report();
		foreach ( $elements as $i => $element ) {
			$text = rtrim( $element['paragraph']['elements'][0]['textRun']['content'] );
			if ( str_starts_with( $text, 'Was the status quo ante' ) ) {
				array_splice( $elements, $i, 1, array( RightsFixture::text( 'Was the status quo ante negative, neutral, or positive?' ), RightsFixture::text( 'Your answer: Neutral' ) ) );
				break;
			}
		}

		$report = RightsFixture::parse( RightsFixture::document( $elements ) );

		$this->assertSame( array(), $report['problems'] );
		$this->assertSame( 'neutral', $report['rating']['ante'] );
	}

	public function test_a_missing_headline_is_an_error() {
		$elements = array_map(
			function ( $e ) {
				foreach ( $e['paragraph']['elements'] as $i => $run ) {
					unset( $e['paragraph']['elements'][ $i ]['textRun']['textStyle']['bold'] );
				}
				return $e;
			},
			RightsFixture::report()
		);

		$report = RightsFixture::parse( RightsFixture::document( $elements ) );

		$this->assertSame( array( 'missing_headline' ), RightsFixture::codes( $report['problems'] ) );
	}

	public function test_a_changed_headline_is_just_a_different_headline() {
		$report = RightsFixture::parse( RightsFixture::document( RightsFixture::report( array( 'headline' => 'Rights have widened.' ) ) ) );

		$this->assertSame( array(), $report['problems'] );
		$this->assertSame( 'Rights have widened.', $report['rating']['headline'] );
	}

	public function test_a_paragraph_with_a_link_inside_prose_stays_prose() {
		$paragraph = array(
			'style' => 'NORMAL_TEXT',
			'text'  => 'See the ministry site for details.',
			'runs'  => array(
				array(
					'text'   => 'See the ',
					'bold'   => false,
					'italic' => false,
					'url'    => '',
				),
				array(
					'text'   => 'ministry site',
					'bold'   => false,
					'italic' => false,
					'url'    => 'https://example.org/ministry',
				),
				array(
					'text'   => ' for details.',
					'bold'   => false,
					'italic' => false,
					'url'    => '',
				),
			),
		);

		$this->assertNull( Doc_Parser::sources( $paragraph ) );
		$this->assertSame( 'See the <a href="https://example.org/ministry">ministry site</a> for details.', Doc_Parser::runs_html( $paragraph['runs'] ) );
	}

	public function test_html_is_escaped_and_only_http_links_survive() {
		$runs = array(
			array(
				'text'   => '<script>x</script> & ',
				'bold'   => true,
				'italic' => false,
				'url'    => '',
			),
			array(
				'text'   => 'click',
				'bold'   => false,
				'italic' => true,
				'url'    => 'javascript:alert(1)',
			),
		);

		$this->assertSame( '<strong>&lt;script&gt;x&lt;/script&gt; &amp; </strong><em>click</em>', Doc_Parser::runs_html( $runs ) );
	}

	public function test_a_doc_without_a_title_still_parses_its_sections() {
		$elements = array_slice( RightsFixture::report(), 1 );

		$report = RightsFixture::parse( RightsFixture::document( $elements ) );

		$this->assertSame( array( 'missing_title' ), RightsFixture::codes( $report['problems'] ) );
		$this->assertSame( 'yes', $report['dimensions']['i1']['answer'] );
	}

	public function test_a_section_added_to_the_structure_is_parsed_without_code_changes() {
		$structure               = wt_rights_structure();
		$structure['sections'][] = array(
			'key'      => 'outlook',
			'heading'  => 'Outlook',
			'type'     => 'prose',
			'optional' => true,
		);
		$elements                = RightsFixture::report();
		$elements[]              = RightsFixture::p( 'HEADING_1', 'Outlook' );
		$elements[]              = RightsFixture::text( 'Elections are due next year.' );

		$report = ( new Doc_Parser( $structure ) )->parse( \WT\Rights\Docs_Adapter::tabs( RightsFixture::document( $elements ) ) );

		$this->assertSame( array(), $report['problems'] );
		$this->assertSame( "<p>Elections are due next year.</p>\n", $report['sections']['outlook']['html'] );
	}

	public function test_a_renamed_heading_parses_through_an_alias() {
		$structure                           = wt_rights_structure();
		$structure['sections'][0]['heading'] = 'Languages';
		$structure['sections'][0]['aliases'] = array( 'Language inventory' );

		$report = ( new Doc_Parser( $structure ) )->parse( \WT\Rights\Docs_Adapter::tabs( RightsFixture::document( RightsFixture::report() ) ) );

		$this->assertSame( array(), $report['problems'] );
		$this->assertArrayHasKey( 'inventory', $report['sections'] );
	}
}
