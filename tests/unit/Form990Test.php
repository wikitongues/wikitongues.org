<?php
use WP_Mock\Tools\TestCase;

class Form990Test extends TestCase {

	public function test_archive_lists_every_filing_newest_tax_year_first() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );

		$query                    = new FakeQuery();
		$query->archive_post_type = 'form_990';
		wt_form_990_archive_query( $query );

		$this->assertSame(
			array(
				'posts_per_page' => -1,
				'meta_key'       => 'tax_year',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			),
			$query->calls
		);
	}

	public function test_other_archives_are_untouched() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );

		$query                    = new FakeQuery();
		$query->archive_post_type = 'videos';
		wt_form_990_archive_query( $query );

		$this->assertSame( array(), $query->calls );
	}

	public function test_secondary_queries_are_untouched() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );

		$query                    = new FakeQuery();
		$query->archive_post_type = 'form_990';
		$query->main_query        = false;
		wt_form_990_archive_query( $query );

		$this->assertSame( array(), $query->calls );
	}

	public function test_admin_list_is_untouched() {
		WP_Mock::userFunction( 'is_admin', array( 'return' => true ) );

		$query                    = new FakeQuery();
		$query->archive_post_type = 'form_990';
		wt_form_990_archive_query( $query );

		$this->assertSame( array(), $query->calls );
	}

	public function test_file_returns_url_and_formatted_size() {
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'args'   => array( 7, 'form_990_file', true ),
				'return' => '42',
			)
		);
		WP_Mock::userFunction(
			'wp_get_attachment_url',
			array(
				'args'   => array( 42 ),
				'return' => 'https://example.org/990.pdf',
			)
		);
		WP_Mock::userFunction(
			'get_attached_file',
			array(
				'args'   => array( 42 ),
				'return' => __FILE__,
			)
		);
		WP_Mock::userFunction(
			'size_format',
			array(
				'args'   => array( filesize( __FILE__ ) ),
				'return' => '3 KB',
			)
		);

		$this->assertSame(
			array(
				'url'  => 'https://example.org/990.pdf',
				'size' => '3 KB',
			),
			wt_form_990_file( 7 )
		);
	}

	public function test_file_is_null_when_nothing_is_attached() {
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => '' ) );

		$this->assertNull( wt_form_990_file( 7 ) );
	}

	public function test_file_is_null_when_the_attachment_is_gone() {
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => '42' ) );
		WP_Mock::userFunction( 'wp_get_attachment_url', array( 'return' => false ) );

		$this->assertNull( wt_form_990_file( 7 ) );
	}

	public function test_size_is_empty_when_the_file_is_missing_on_disk() {
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => '42' ) );
		WP_Mock::userFunction( 'wp_get_attachment_url', array( 'return' => 'https://example.org/990.pdf' ) );
		WP_Mock::userFunction( 'get_attached_file', array( 'return' => '/nonexistent/990.pdf' ) );

		$this->assertSame(
			array(
				'url'  => 'https://example.org/990.pdf',
				'size' => '',
			),
			wt_form_990_file( 7 )
		);
	}
}
