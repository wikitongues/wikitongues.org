<?php
use WP_Mock\Tools\TestCase;

class ContentModelTest extends TestCase {

	public function test_a_region_slug_resolves_to_the_region() {
		WP_Mock::userFunction( 'sanitize_title' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'term_exists' )->with( 'south-central-asia', 'rights_region' )->andReturn( array( 'term_id' => 5 ) );

		$this->assertSame( array( 'rights_region' => 'south-central-asia' ), wt_rights_resolve_request( array( 'wt_rights_slug' => 'south-central-asia' ) ) );
	}

	public function test_any_other_slug_resolves_to_a_report() {
		WP_Mock::userFunction( 'sanitize_title' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'term_exists' )->andReturn( null );

		$this->assertSame(
			array(
				'post_type'      => 'rights_reports',
				'rights_reports' => 'afghanistan',
				'name'           => 'afghanistan',
			),
			wt_rights_resolve_request( array( 'wt_rights_slug' => 'afghanistan' ) )
		);
	}

	public function test_other_requests_are_left_alone() {
		$this->assertSame( array( 'pagename' => 'about' ), wt_rights_resolve_request( array( 'pagename' => 'about' ) ) );
	}

	/**
	 * @dataProvider readers
	 */
	public function test_who_can_read_a_region( string $status, array $caps, bool $expected ) {
		WP_Mock::userFunction( 'get_term_meta' )->andReturn( $status );
		WP_Mock::userFunction( 'current_user_can' )->andReturnUsing( fn( string $cap ): bool => in_array( $cap, $caps, true ) );

		$this->assertSame( $expected, wt_rights_can_read_region( 5 ) );
	}

	public static function readers(): array {
		$press = array( 'read_private_rights_reports' );
		$admin = array( 'read_private_rights_reports', 'edit_rights_reports' );
		return array(
			'released, anyone'         => array( 'released', array(), true ),
			'embargoed, anonymous'     => array( 'embargoed', array(), false ),
			'embargoed, press'         => array( 'embargoed', $press, true ),
			'embargoed, admin'         => array( 'embargoed', $admin, true ),
			'upcoming, press'          => array( 'upcoming', $press, false ),
			'upcoming, admin'          => array( 'upcoming', $admin, true ),
			'unset counts as upcoming' => array( '', $press, false ),
		);
	}
}
