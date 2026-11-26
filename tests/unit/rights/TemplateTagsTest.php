<?php
use WP_Mock\Tools\TestCase;

class TemplateTagsTest extends TestCase {

	private function post( int $id ): WP_Post {
		/** @var WP_Post $post */
		$post     = Mockery::mock( 'WP_Post' );
		$post->ID = $id;
		return $post;
	}

	public function test_the_announcement_is_milestone_one_so_regions_start_at_two() {
		$this->assertSame( 2, wt_rights_milestone_number( 1 ), 'South and Central Asia, release order 1' );
		$this->assertSame( 8, wt_rights_milestone_number( 7 ) );
		$this->assertSame( 9, wt_rights_milestone_total( 7 ), 'the Complete Edition is last' );
	}

	public function test_donate_links_use_the_site_donate_page_until_a_campaign_is_set() {
		WP_Mock::userFunction( 'home_url' )->with( '/donate/' )->andReturn( 'https://example.org/donate/' );

		$this->assertSame( 'https://example.org/donate/', wt_rights_donate_url() );
	}

	public function test_the_tally_counts_answers_and_skips_empty_ones() {
		$answers = array(
			1 => array(
				'i1'   => 'yes',
				'ante' => 'positive',
			),
			2 => array(
				'i1'   => 'yes',
				'ante' => '',
			),
			3 => array( 'i1' => 'unclear' ),
		);
		WP_Mock::userFunction( 'get_post_meta' )->andReturnUsing(
			fn( int $id ): string => json_encode( array( 'answers' => $answers[ $id ] ) )
		);

		$tally = wt_rights_tally( array( $this->post( 1 ), $this->post( 2 ), $this->post( 3 ) ) );

		$this->assertSame(
			array(
				'yes'     => 2,
				'unclear' => 1,
			),
			$tally['i1']
		);
		$this->assertSame( array( 'positive' => 1 ), $tally['ante'] );
	}

	public function test_a_report_with_broken_meta_is_an_empty_array() {
		WP_Mock::userFunction( 'get_post_meta' )->andReturn( '{"not json' );

		$this->assertSame( array(), wt_rights_report( 7 ) );
	}

	public function test_answers_become_labelled_pills() {
		WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'sanitize_html_class' )->andReturnArg( 0 );

		$this->assertSame( '<span class="wt-rights__answer wt-rights__answer--not-applicable">Not applicable</span>', wt_rights_answer_pill( 'not applicable' ) );
		$this->assertStringContainsString( 'Not recorded', wt_rights_answer_pill( '' ) );
	}

	public function test_language_codes_link_only_when_the_language_exists() {
		WP_Mock::userFunction( 'get_posts' )->andReturn( array( 11 ) );
		WP_Mock::userFunction( 'get_post_field' )->with( 'post_name', 11 )->andReturn( 'prs' );
		WP_Mock::userFunction( 'get_permalink' )->with( 11 )->andReturn( 'https://example.org/languages/prs/' );
		WP_Mock::userFunction( 'esc_url' )->andReturnArg( 0 );

		$html = wt_rights_link_languages( '<p>Dari (prs) and Zemiaki (zzz).</p>' );

		$this->assertSame( '<p>Dari (<a href="https://example.org/languages/prs/">prs</a>) and Zemiaki (zzz).</p>', $html );
	}

	public function test_text_without_codes_makes_no_query() {
		WP_Mock::userFunction( 'get_posts' )->never();

		$this->assertSame( '<p>No codes here (or here).</p>', wt_rights_link_languages( '<p>No codes here (or here).</p>' ) );
	}

	public function test_the_embargo_notice_is_hidden_from_visitors_who_cannot_read_the_region() {
		WP_Mock::userFunction( 'get_term_meta' )->andReturn( 'upcoming' );
		WP_Mock::userFunction( 'current_user_can' )->andReturn( false );

		$this->assertSame( '', wt_rights_embargo_notice( 5 ) );
	}
}
