<?php
use WP_Mock\Tools\TestCase;
use WT\Rights\Pdf_Resolver;

class PdfAndPressTest extends TestCase {

	/**
	 * @dataProvider folders
	 */
	public function test_the_private_folder_must_be_outside_the_web_root( string $dir, bool $outside ) {
		$this->assertSame( $outside, wt_rights_outside_web_root( $dir, '/home/wt/public_html' ) );
	}

	public static function folders(): array {
		return array(
			'home folder'                   => array( '/home/wt/rights-private', true ),
			'inside WordPress'              => array( '/home/wt/public_html/wp-content/rights', false ),
			'staging inside production'     => array( '/home/wt/public_html/staging-private', false ),
			'a MAMP htdocs folder'          => array( '/Applications/MAMP/htdocs/rights-private', false ),
			'a sibling that only starts so' => array( '/home/wt/public_html2/x', true ),
			'unresolved'                    => array( '', false ),
		);
	}

	public function test_pdf_names_carry_slug_version_and_hash() {
		WP_Mock::userFunction( 'sanitize_file_name' )->andReturnArg( 0 );

		$this->assertSame( 'afghanistan-v1.1-8d093860.pdf', wt_rights_pdf_name( 'afghanistan', '1.1', '8d093860aaaabbbb' ) );
	}

	public function test_print_tokens_differ_by_report_and_version() {
		WP_Mock::userFunction( 'wp_salt' )->andReturn( 'salt' );

		$token = wt_rights_print_token( 'afghanistan', '1.0' );
		$this->assertSame( 32, strlen( $token ) );
		$this->assertNotSame( $token, wt_rights_print_token( 'afghanistan', '1.1' ) );
		$this->assertNotSame( $token, wt_rights_print_token( 'nepal', '1.0' ) );
	}

	public function test_the_resolver_gives_a_pdf_only_for_a_published_report() {
		WP_Mock::userFunction( 'get_post_type' )->andReturn( 'rights_reports' );
		WP_Mock::userFunction( 'get_post_meta' )->andReturn( '55' );
		WP_Mock::userFunction( 'wp_get_attachment_url' )->with( 55 )->andReturn( 'https://example.org/rights/a.pdf' );
		$status = 'private';
		WP_Mock::userFunction( 'get_post_status' )->andReturnUsing(
			function () use ( &$status ) {
				return $status;
			}
		);
		$resolver = new Pdf_Resolver();

		$this->assertNull( $resolver->resolve( 9 ) );
		$status = 'publish';
		$this->assertSame( 'https://example.org/rights/a.pdf', $resolver->resolve( 9 ) );
		$this->assertSame( 'media', $resolver->storage_type() );
	}

	public function test_press_accounts_land_on_the_report_hub() {
		/** @var WP_User&\Mockery\MockInterface $press */
		$press        = Mockery::mock( 'WP_User' );
		$press->roles = array( 'press' );
		$press->shouldReceive( 'exists' )->andReturn( true );
		WP_Mock::userFunction( 'user_can' )->andReturn( false );
		WP_Mock::userFunction( 'home_url' )->andReturnUsing( fn( string $path ): string => 'https://example.org' . $path );

		$this->assertSame( 'https://example.org/language-rights/report/', wt_rights_press_login_redirect( '/wp-admin/', '', $press ) );
	}

	public function test_an_editor_with_the_press_role_too_is_not_redirected() {
		/** @var WP_User&\Mockery\MockInterface $editor */
		$editor        = Mockery::mock( 'WP_User' );
		$editor->roles = array( 'press', 'editor' );
		$editor->shouldReceive( 'exists' )->andReturn( true );
		WP_Mock::userFunction( 'user_can' )->andReturn( true );

		$this->assertSame( '/wp-admin/', wt_rights_press_login_redirect( '/wp-admin/', '', $editor ) );
	}
}
