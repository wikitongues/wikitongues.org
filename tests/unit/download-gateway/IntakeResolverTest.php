<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\IntakeResolver;

class IntakeResolverTest extends TestCase {

	// -------------------------------------------------------------------------
	// resolve() — all-inherit path falls through to global
	// -------------------------------------------------------------------------

	public function test_resolve_returns_empty_set_when_global_is_none(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => 'inherit' ) );
		WP_Mock::userFunction(
			'get_option',
			array(
				'return_arg' => 1, // return second arg (default)
			)
		);

		$result = IntakeResolver::resolve( 1 );
		$this->assertSame( '', $result['set'] );
		$this->assertFalse( $result['always'] );
	}

	public function test_resolve_returns_empty_when_post_type_not_found(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => false ) );

		$result = IntakeResolver::resolve( 999 );
		$this->assertSame( '', $result['set'] );
		$this->assertFalse( $result['always'] );
	}

	// -------------------------------------------------------------------------
	// resolve_set() — tier 1: per-record postmeta
	// -------------------------------------------------------------------------

	public function test_resolve_returns_set_name_from_postmeta(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		// Postmeta returns a concrete set name.
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => function ( $post_id, $key ) {
					if ( IntakeResolver::META_KEY_SET === $key ) {
						return 'standard';
					}
					return 'inherit'; // always key inherits
				},
			)
		);
		WP_Mock::userFunction( 'get_option', array( 'return_arg' => 1 ) );

		$result = IntakeResolver::resolve( 1 );
		$this->assertSame( 'standard', $result['set'] );
	}

	public function test_resolve_returns_empty_when_postmeta_is_none(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => function ( $post_id, $key ) {
					if ( IntakeResolver::META_KEY_SET === $key ) {
						return 'none';
					}
					return '0'; // always = false
				},
			)
		);

		$result = IntakeResolver::resolve( 1 );
		$this->assertSame( '', $result['set'] );
	}

	// -------------------------------------------------------------------------
	// resolve_set() — tier 2: per-CPT option
	// -------------------------------------------------------------------------

	public function test_resolve_falls_through_to_cpt_option_when_meta_inherits(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		// Postmeta returns inherit for both keys.
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => 'inherit' ) );
		// CPT option returns 'research' for the set key, '' for always.
		WP_Mock::userFunction(
			'get_option',
			array(
				'return' => function ( $key, $fallback = '' ) {
					if ( str_contains( $key, 'intake_set_videos' ) ) {
						return 'research';
					}
					return $fallback;
				},
			)
		);

		$result = IntakeResolver::resolve( 1 );
		$this->assertSame( 'research', $result['set'] );
	}

	public function test_resolve_cpt_none_returns_empty_set(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => 'inherit' ) );
		WP_Mock::userFunction(
			'get_option',
			array(
				'return' => function ( $key, $fallback = '' ) {
					if ( str_contains( $key, 'intake_set_videos' ) ) {
						return 'none';
					}
					return $fallback;
				},
			)
		);

		$result = IntakeResolver::resolve( 1 );
		$this->assertSame( '', $result['set'] );
	}

	// -------------------------------------------------------------------------
	// resolve_always() — tier 1: per-record postmeta
	// -------------------------------------------------------------------------

	public function test_resolve_always_true_from_postmeta(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => function ( $post_id, $key ) {
					if ( IntakeResolver::META_KEY_ALWAYS === $key ) {
						return '1';
					}
					return 'inherit';
				},
			)
		);
		WP_Mock::userFunction( 'get_option', array( 'return_arg' => 1 ) );

		$result = IntakeResolver::resolve( 1 );
		$this->assertTrue( $result['always'] );
	}

	public function test_resolve_always_false_from_postmeta(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => function ( $post_id, $key ) {
					if ( IntakeResolver::META_KEY_ALWAYS === $key ) {
						return '0';
					}
					return 'inherit';
				},
			)
		);
		WP_Mock::userFunction( 'get_option', array( 'return_arg' => 1 ) );

		$result = IntakeResolver::resolve( 1 );
		$this->assertFalse( $result['always'] );
	}

	// -------------------------------------------------------------------------
	// resolve_always() — tier 3: global option
	// -------------------------------------------------------------------------

	public function test_resolve_always_true_from_global_option(): void {
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => 'inherit' ) );
		WP_Mock::userFunction(
			'get_option',
			array(
				'return' => function ( $key, $fallback = '' ) {
					if ( str_contains( $key, 'intake_always' ) ) {
						return '1';
					}
					return $fallback;
				},
			)
		);

		$result = IntakeResolver::resolve( 1 );
		$this->assertTrue( $result['always'] );
	}
}
