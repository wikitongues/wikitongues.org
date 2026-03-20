<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\PersonCookie;

class PersonCookieTest extends TestCase {

	// -------------------------------------------------------------------------
	// sign()
	// -------------------------------------------------------------------------

	public function test_sign_returns_string_with_id_dot_hmac(): void {
		$value = PersonCookie::sign( 42 );
		$this->assertMatchesRegularExpression( '/^42\.[0-9a-f]{64}$/', $value );
	}

	public function test_sign_is_deterministic(): void {
		$this->assertSame( PersonCookie::sign( 7 ), PersonCookie::sign( 7 ) );
	}

	public function test_sign_differs_for_different_ids(): void {
		$this->assertNotSame( PersonCookie::sign( 1 ), PersonCookie::sign( 2 ) );
	}

	// -------------------------------------------------------------------------
	// verify() — valid values
	// -------------------------------------------------------------------------

	public function test_verify_returns_person_id_for_valid_cookie(): void {
		$cookie = PersonCookie::sign( 42 );
		$result = PersonCookie::verify( $cookie );
		$this->assertSame( 42, $result );
	}

	public function test_verify_works_for_various_person_ids(): void {
		foreach ( array( 1, 99, 1000, 999999 ) as $id ) {
			$this->assertSame( $id, PersonCookie::verify( PersonCookie::sign( $id ) ) );
		}
	}

	// -------------------------------------------------------------------------
	// verify() — invalid / tampered values
	// -------------------------------------------------------------------------

	public function test_verify_returns_false_for_raw_integer_string(): void {
		$this->assertFalse( PersonCookie::verify( '42' ) );
	}

	public function test_verify_returns_false_for_zero_id(): void {
		$this->assertFalse( PersonCookie::verify( '0.abc' ) );
	}

	public function test_verify_returns_false_for_negative_id(): void {
		$this->assertFalse( PersonCookie::verify( '-1.abc' ) );
	}

	public function test_verify_returns_false_for_non_numeric_id(): void {
		$this->assertFalse( PersonCookie::verify( 'abc.abc' ) );
	}

	public function test_verify_returns_false_for_tampered_hmac(): void {
		$cookie   = PersonCookie::sign( 42 );
		$tampered = substr_replace( $cookie, 'aaaaaaa', -7 );
		$this->assertFalse( PersonCookie::verify( $tampered ) );
	}

	public function test_verify_returns_false_for_swapped_id(): void {
		// Take HMAC from id=1, try to use it with id=2.
		$parts    = explode( '.', PersonCookie::sign( 1 ), 2 );
		$tampered = '2.' . $parts[1];
		$this->assertFalse( PersonCookie::verify( $tampered ) );
	}

	public function test_verify_returns_false_for_empty_string(): void {
		$this->assertFalse( PersonCookie::verify( '' ) );
	}

	public function test_verify_returns_false_for_malformed_no_dot(): void {
		$this->assertFalse( PersonCookie::verify( 'nodothere' ) );
	}
}
