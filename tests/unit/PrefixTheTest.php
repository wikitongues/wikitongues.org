<?php
use WP_Mock\Tools\TestCase;

class PrefixTheTest extends TestCase {

	public function test_americas_gets_prefix() {
		$this->assertSame( 'the Americas', wt_prefix_the( 'Americas' ) );
	}

	public function test_caribbean_gets_prefix() {
		$this->assertSame( 'the Caribbean', wt_prefix_the( 'Caribbean' ) );
	}

	public function test_sahel_gets_prefix() {
		$this->assertSame( 'the Sahel', wt_prefix_the( 'Sahel' ) );
	}

	public function test_gambia_gets_prefix() {
		$this->assertSame( 'the Gambia', wt_prefix_the( 'Gambia' ) );
	}

	public function test_bahamas_gets_prefix() {
		$this->assertSame( 'the Bahamas', wt_prefix_the( 'Bahamas' ) );
	}

	public function test_ordinary_name_unchanged() {
		$this->assertSame( 'Uganda', wt_prefix_the( 'Uganda' ) );
	}

	public function test_empty_string_unchanged() {
		$this->assertSame( '', wt_prefix_the( '' ) );
	}

	public function test_match_is_case_sensitive() {
		// Lowercase variants must not trigger the prefix.
		$this->assertSame( 'americas', wt_prefix_the( 'americas' ) );
		$this->assertSame( 'bahamas', wt_prefix_the( 'bahamas' ) );
	}
}
