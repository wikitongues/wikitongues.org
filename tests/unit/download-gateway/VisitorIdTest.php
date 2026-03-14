<?php

use PHPUnit\Framework\TestCase;
use WT\DownloadGateway\VisitorId;

class VisitorIdTest extends TestCase {

	// --- generate() ---

	public function test_generate_returns_32_char_hex_string(): void {
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{32}$/', VisitorId::generate() );
	}

	public function test_generate_returns_different_values(): void {
		$this->assertNotSame( VisitorId::generate(), VisitorId::generate() );
	}

	// --- from_cookies() ---

	public function test_from_cookies_returns_valid_id(): void {
		$id = str_repeat( 'a', 32 );
		$this->assertSame( $id, VisitorId::from_cookies( array( 'gateway_vid' => $id ) ) );
	}

	public function test_from_cookies_returns_null_when_cookie_absent(): void {
		$this->assertNull( VisitorId::from_cookies( array() ) );
	}

	public function test_from_cookies_returns_null_for_wrong_length(): void {
		$this->assertNull( VisitorId::from_cookies( array( 'gateway_vid' => 'abc123' ) ) );
	}

	public function test_from_cookies_returns_null_for_non_hex_characters(): void {
		$this->assertNull( VisitorId::from_cookies( array( 'gateway_vid' => str_repeat( 'z', 32 ) ) ) );
	}

	public function test_from_cookies_returns_null_for_uppercase_hex(): void {
		// Cookie values must be lowercase — uppercase suggests external injection.
		$this->assertNull( VisitorId::from_cookies( array( 'gateway_vid' => str_repeat( 'A', 32 ) ) ) );
	}

	public function test_from_cookies_returns_null_for_non_string_value(): void {
		$this->assertNull( VisitorId::from_cookies( array( 'gateway_vid' => 12345 ) ) );
	}
}
