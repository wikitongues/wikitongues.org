<?php
use WP_Mock\Tools\TestCase;

class GalleryPaginationTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction( 'esc_attr', array( 'return_arg' => 0 ) );
	}

	private function make_query( int $max_pages ): stdClass {
		$query                = new stdClass();
		$query->max_num_pages = $max_pages;
		return $query;
	}

	public function test_page_1_has_no_previous_button() {
		$html = generate_gallery_pagination( $this->make_query( 10 ), 'gallery-1', 1, 9, '' );
		$this->assertStringNotContainsString( 'Previous', $html );
	}

	public function test_page_1_has_next_button() {
		$html = generate_gallery_pagination( $this->make_query( 10 ), 'gallery-1', 1, 9, '' );
		$this->assertStringContainsString( 'Next', $html );
	}

	public function test_last_page_has_previous_button() {
		$html = generate_gallery_pagination( $this->make_query( 10 ), 'gallery-1', 10, 9, '' );
		$this->assertStringContainsString( 'Previous', $html );
	}

	public function test_last_page_has_no_next_button() {
		$html = generate_gallery_pagination( $this->make_query( 10 ), 'gallery-1', 10, 9, '' );
		$this->assertStringNotContainsString( 'Next', $html );
	}

	public function test_middle_page_has_both_buttons() {
		$html = generate_gallery_pagination( $this->make_query( 10 ), 'gallery-1', 5, 9, '' );
		$this->assertStringContainsString( 'Previous', $html );
		$this->assertStringContainsString( 'Next', $html );
	}

	public function test_active_class_on_current_page() {
		$html = generate_gallery_pagination( $this->make_query( 10 ), 'gallery-1', 3, 9, '' );
		$this->assertMatchesRegularExpression( '#class="page-numbers active"[^>]*data-page="3"#', $html );
	}

	public function test_window_anchored_near_start() {
		// Page 2 of 10, window of 9: should show pages 1–9.
		$html = generate_gallery_pagination( $this->make_query( 10 ), 'gallery-1', 2, 9, '' );
		$this->assertStringContainsString( 'data-page="1"', $html );
		$this->assertStringContainsString( 'data-page="9"', $html );
		$this->assertStringNotContainsString( 'data-page="10"', $html );
	}

	public function test_window_anchored_near_end() {
		// Page 9 of 10, window of 9: should show pages 2–10.
		$html = generate_gallery_pagination( $this->make_query( 10 ), 'gallery-1', 9, 9, '' );
		$this->assertStringContainsString( 'data-page="2"', $html );
		$this->assertStringContainsString( 'data-page="10"', $html );
		$this->assertStringNotContainsString( 'data-page="1"', $html );
	}
}
