<?php
use WP_Mock\Tools\TestCase;

class GetDomainFromUrlTest extends TestCase {

	public function test_strips_www_prefix() {
		$this->assertSame( 'example.com', getDomainFromUrl( 'https://www.example.com/path' ) );
	}

	public function test_returns_host_without_www_unchanged() {
		$this->assertSame( 'example.com', getDomainFromUrl( 'https://example.com/page' ) );
	}

	public function test_preserves_non_www_subdomain() {
		$this->assertSame( 'blog.example.com', getDomainFromUrl( 'https://blog.example.com/post' ) );
	}

	public function test_ignores_path_and_query_string() {
		$this->assertSame( 'example.com', getDomainFromUrl( 'https://example.com/a/b?q=1#anchor' ) );
	}

	public function test_works_without_path() {
		$this->assertSame( 'example.com', getDomainFromUrl( 'https://www.example.com' ) );
	}
}
