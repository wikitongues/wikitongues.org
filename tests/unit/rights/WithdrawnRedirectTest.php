<?php
use WP_Mock\Tools\TestCase;

class WithdrawnRedirectTest extends TestCase {

	public function test_a_report_the_visitor_cannot_read_goes_to_its_region() {
		$region = Mockery::mock( 'WP_Term' );
		WP_Mock::userFunction( 'get_posts' )->andReturn( array( 5 ) );
		WP_Mock::userFunction( 'get_the_terms' )->with( 5, 'rights_region' )->andReturn( array( $region ) );
		WP_Mock::userFunction( 'get_term_link' )->with( $region )->andReturn( 'https://example.org/language-rights/report/testland-region/' );

		$this->assertSame( 'https://example.org/language-rights/report/testland-region/#countries', wt_rights_withdrawn_target( 'testland' ) );
	}

	public function test_an_unknown_slug_stays_a_404() {
		WP_Mock::userFunction( 'get_posts' )->andReturn( array() );

		$this->assertSame( '', wt_rights_withdrawn_target( 'nowhere' ) );
		$this->assertSame( '', wt_rights_withdrawn_target( '' ) );
	}

	public function test_a_report_without_a_region_stays_a_404() {
		WP_Mock::userFunction( 'get_posts' )->andReturn( array( 5 ) );
		WP_Mock::userFunction( 'get_the_terms' )->andReturn( false );

		$this->assertSame( '', wt_rights_withdrawn_target( 'testland' ) );
	}
}
