<?php
use WP_Mock\Tools\TestCase;

class EnvironmentTest extends TestCase {

	/** @var string */
	private $original_http_host = '';

	public function setUp(): void {
		parent::setUp();
		$this->original_http_host = $_SERVER['HTTP_HOST'] ?? '';
	}

	public function tearDown(): void {
		$_SERVER['HTTP_HOST'] = $this->original_http_host;
		parent::tearDown();
	}

	public function test_returns_localhost_for_localhost_host() {
		$_SERVER['HTTP_HOST'] = 'localhost';
		$this->assertSame( 'localhost', get_environment() );
	}

	public function test_returns_localhost_for_localhost_with_port() {
		$_SERVER['HTTP_HOST'] = 'localhost:8888';
		$this->assertSame( 'localhost', get_environment() );
	}

	public function test_returns_staging_for_staging_host() {
		$_SERVER['HTTP_HOST'] = 'staging.wikitongues.org';
		$this->assertSame( 'staging', get_environment() );
	}

	public function test_returns_empty_string_for_production() {
		$_SERVER['HTTP_HOST'] = 'wikitongues.org';
		$this->assertSame( '', get_environment() );
	}
}
