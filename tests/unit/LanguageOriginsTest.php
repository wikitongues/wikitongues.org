<?php
use WP_Mock\Tools\TestCase;

class LanguageOriginsTest extends TestCase {

	public function test_split_trims_and_drops_empty_names() {
		$this->assertSame(
			array( 'South Korea', 'North Korea' ),
			wt_split_origin_titles( " South Korea,North Korea ,, \xc2\xa0" )
		);
	}

	public function test_split_of_blank_text_is_empty() {
		$this->assertSame( array(), wt_split_origin_titles( '' ) );
	}

	public function test_meta_clauses_empty_for_blank_term() {
		$this->assertSame( array(), wt_origin_meta_clauses( '   ' ) );
	}

	public function test_meta_clauses_match_quoted_territory_ids() {
		WP_Mock::userFunction( 'get_posts', array( 'return' => array( 20613, 20575 ) ) );

		$clauses = wt_origin_meta_clauses( 'Korea' );

		$this->assertCount( 2, $clauses );
		$this->assertSame(
			array(
				'key'     => 'nations_of_origin',
				'value'   => '"20613"',
				'compare' => 'LIKE',
			),
			$clauses[0]
		);
	}

	public function test_meta_clauses_empty_when_no_territory_matches() {
		WP_Mock::userFunction( 'get_posts', array( 'return' => array() ) );

		$this->assertSame( array(), wt_origin_meta_clauses( 'Atlantis' ) );
	}
}
