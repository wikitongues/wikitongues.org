<?php
use WP_Mock\Tools\TestCase;

class WtIconTest extends TestCase {

	public function test_known_name_returns_svg_element() {
		$result = wt_icon( 'bars' );
		$this->assertStringContainsString( '<svg', $result );
	}

	public function test_unknown_name_returns_empty_string() {
		$this->assertSame( '', wt_icon( 'nonexistent-icon' ) );
	}

	public function test_empty_name_returns_empty_string() {
		$this->assertSame( '', wt_icon( '' ) );
	}

	public function test_svg_is_aria_hidden() {
		$this->assertStringContainsString( 'aria-hidden="true"', wt_icon( 'envelope' ) );
	}

	public function test_svg_uses_current_color() {
		$this->assertStringContainsString( 'fill="currentColor"', wt_icon( 'envelope' ) );
	}

	/**
	 * @dataProvider all_icon_names
	 */
	public function test_all_registered_names_return_svg( string $name ) {
		$result = wt_icon( $name );
		$this->assertNotEmpty( $result, "wt_icon('{$name}') returned empty — icon may have been removed" );
		$this->assertStringContainsString( '<svg', $result );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function all_icon_names(): array {
		return array(
			'arrow-right-long' => array( 'arrow-right-long' ),
			'bars'             => array( 'bars' ),
			'envelope'         => array( 'envelope' ),
			'instagram'        => array( 'instagram' ),
			'link'             => array( 'link' ),
			'linkedin'         => array( 'linkedin' ),
			'square-email'     => array( 'square-email' ),
			'square-facebook'  => array( 'square-facebook' ),
			'tiktok'           => array( 'tiktok' ),
			'x-twitter'        => array( 'x-twitter' ),
			'xmark'            => array( 'xmark' ),
			'youtube'          => array( 'youtube' ),
		);
	}
}
