<?php
use WP_Mock\Tools\TestCase;

class AnalyticsTest extends TestCase {

	public function test_admins_are_tagged_admin() {
		WP_Mock::userFunction( 'current_user_can' )->with( 'edit_rights_reports' )->andReturn( true );

		$this->assertSame( 'admin', wt_rights_viewer_role() );
	}

	public function test_the_public_has_no_role() {
		WP_Mock::userFunction( 'current_user_can' )->andReturn( false );
		$visitor = Mockery::mock( 'WP_User' );
		$visitor->shouldReceive( 'exists' )->andReturn( false );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( $visitor );

		$this->assertSame( '', wt_rights_viewer_role() );
	}

	public function test_pages_outside_the_report_push_nothing() {
		WP_Mock::userFunction( 'get_query_var' )->andReturn( '' );
		WP_Mock::userFunction( 'is_page_template' )->andReturn( false );
		WP_Mock::userFunction( 'is_tax' )->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )->andReturn( false );

		$this->assertNull( wt_rights_page_context() );
		$this->expectOutputString( '' );
		wt_rights_push_page_context();
	}

	public function test_the_hub_pushes_its_page_type() {
		WP_Mock::userFunction( 'get_query_var' )->with( 'wt_rights_hub' )->andReturn( '1' );
		WP_Mock::userFunction( 'current_user_can' )->andReturn( false );
		$visitor = Mockery::mock( 'WP_User' );
		$visitor->shouldReceive( 'exists' )->andReturn( false );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( $visitor );

		$this->assertSame(
			array(
				'rights_page' => 'hub',
				'viewer_role' => '',
			),
			wt_rights_page_context()
		);
	}

	public function test_the_project_page_pushes_its_page_type() {
		WP_Mock::userFunction( 'get_query_var' )->andReturn( '' );
		WP_Mock::userFunction( 'is_page_template' )->with( 'template-rights-project.php' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->andReturn( false );
		$visitor = Mockery::mock( 'WP_User' );
		$visitor->shouldReceive( 'exists' )->andReturn( false );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( $visitor );

		$this->assertSame( 'project', wt_rights_page_context()['rights_page'] );
	}
}
