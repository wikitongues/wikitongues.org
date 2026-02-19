<?php
use PHPUnit\Framework\TestCase;

class ImportCaptionsTest extends TestCase {

	public function test_safe_dropbox_url_encodes_spaces_in_path() {
		$url    = 'https://www.dropbox.com/sh/my file/video.mp4';
		$result = safe_dropbox_url( $url );
		$this->assertStringContainsString( 'my%20file', $result );
		$this->assertStringNotContainsString( 'my file', $result );
	}

	public function test_safe_dropbox_url_preserves_query_string() {
		$url    = 'https://www.dropbox.com/sh/file.mp4?dl=0';
		$result = safe_dropbox_url( $url );
		$this->assertStringContainsString( '?dl=0', $result );
		$this->assertStringContainsString( 'file.mp4', $result );
	}

	public function test_safe_dropbox_url_leaves_clean_path_unchanged() {
		$url    = 'https://www.dropbox.com/sh/abc123/lecture.mp4?dl=0';
		$result = safe_dropbox_url( $url );
		$this->assertStringContainsString( '/sh/abc123/lecture.mp4', $result );
		$this->assertStringContainsString( '?dl=0', $result );
	}

	public function test_safe_dropbox_url_returns_original_when_no_path() {
		$url    = 'https://example.com';
		$result = safe_dropbox_url( $url );
		$this->assertSame( $url, $result );
	}

	public function test_get_safe_value_returns_string_unchanged() {
		$this->assertSame( 'hello', get_safe_value( 'hello' ) );
	}

	public function test_get_safe_value_joins_array_with_comma_space() {
		$this->assertSame( 'a, b, c', get_safe_value( array( 'a', 'b', 'c' ) ) );
	}

	public function test_get_safe_value_returns_empty_string() {
		$this->assertSame( '', get_safe_value( '' ) );
	}
}
