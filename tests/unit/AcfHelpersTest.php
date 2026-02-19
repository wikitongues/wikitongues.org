<?php
use WP_Mock\Tools\TestCase;

class AcfHelpersTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction( 'esc_attr', array( 'return_arg' => 0 ) );
	}

	public function test_returns_primary_when_non_empty() {
		$this->assertSame( 'primary', wt_meta_value( 'primary', 'fallback' ) );
	}

	public function test_returns_fallback_when_primary_is_empty_string() {
		$this->assertSame( 'fallback', wt_meta_value( '', 'fallback' ) );
	}

	public function test_returns_fallback_when_primary_is_null() {
		$this->assertSame( 'fallback', wt_meta_value( null, 'fallback' ) );
	}
}
