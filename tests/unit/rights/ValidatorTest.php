<?php
use PHPUnit\Framework\TestCase;
use WT\Rights\Validator;

class ValidatorTest extends TestCase {

	private function validator(): Validator {
		return new Validator( wt_rights_structure() );
	}

	/** Airtable answers that match RightsFixture::report()'s defaults, in Airtable's own casing. */
	private function row( array $answers = array(), string $status = 'Completed' ): array {
		return array(
			'record_id' => 'recTestland00001',
			'name'      => 'Testland',
			'status'    => $status,
			'answers'   => $answers + array(
				'i1'    => 'yes',
				'i2'    => 'unclear',
				'i3'    => 'no',
				'i4'    => 'yes',
				'i5'    => 'not applicable',
				'ante'  => 'positive',
				'trend' => 'negative',
			),
		);
	}

	private function parsed(): array {
		return RightsFixture::parse( RightsFixture::document( RightsFixture::report() ) );
	}

	public function test_a_doc_that_matches_airtable_has_no_problems() {
		$this->assertSame( array(), $this->validator()->report( $this->row(), $this->parsed() ) );
	}

	public function test_a_doc_answer_that_differs_from_airtable_is_an_error() {
		$problems = $this->validator()->report( $this->row( array( 'i3' => 'unclear' ) ), $this->parsed() );

		$this->assertSame( array( 'answer_mismatch' ), RightsFixture::codes( $problems ) );
		$this->assertStringContainsString( 'the Doc says "no", Airtable says "unclear"', $problems[0]['message'] );
	}

	public function test_a_rating_mismatch_is_caught_too() {
		$problems = $this->validator()->report( $this->row( array( 'trend' => 'neutral' ) ), $this->parsed() );

		$this->assertSame( array( 'answer_mismatch' ), RightsFixture::codes( $problems ) );
	}

	public function test_an_empty_airtable_answer_is_an_error() {
		$problems = $this->validator()->report( $this->row( array( 'i5' => '' ) ), $this->parsed() );

		$this->assertSame( array( 'airtable_missing_answer' ), RightsFixture::codes( $problems ) );
	}

	public function test_the_docs_own_problems_are_included() {
		$parsed   = RightsFixture::parse( RightsFixture::document( RightsFixture::report( array( 'answers' => array( 'i2' => null ) ) ) ) );
		$problems = $this->validator()->report( $this->row(), $parsed );

		$this->assertSame( array( 'missing_answer' ), RightsFixture::codes( $problems ) );
	}

	public function test_kristens_status_names_map_to_release_roles() {
		$v = $this->validator();

		$this->assertSame( 'publish', $v->status_role( 'Completed' ) );
		$this->assertSame( 'held', $v->status_role( 'held' ) );
		$this->assertSame( 'blocking', $v->status_role( 'Pending review' ) );
		$this->assertSame( 'not_started', $v->status_role( 'Unitiated' ) );
		$this->assertSame( 'unknown', $v->status_role( 'Locked' ) );
	}

	public function test_a_region_is_blocked_by_unfinished_countries_only() {
		$rows = array(
			array_merge( $this->row(), array( 'name' => 'Testland' ) ),
			array_merge( $this->row( array(), 'Held' ), array( 'name' => 'Holdland' ) ),
			array_merge( $this->row( array(), 'Unitiated' ), array( 'name' => 'Futureland' ) ),
			array_merge( $this->row( array(), 'Fact checking' ), array( 'name' => 'Checkland' ) ),
		);

		$problems = $this->validator()->region( $rows );

		$this->assertSame( array( 'region_blocked' ), RightsFixture::codes( $problems ) );
		$this->assertStringStartsWith( 'Checkland', $problems[0]['message'] );
	}

	public function test_has_errors_ignores_warnings() {
		$warning = array(
			'code'     => 'story_card_long',
			'severity' => 'warning',
			'message'  => '',
		);

		$this->assertFalse( Validator::has_errors( array( $warning ) ) );
		$this->assertTrue( Validator::has_errors( array( $warning, array( 'severity' => 'error' ) + $warning ) ) );
	}
}
