<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\DocumentFileResolver;
use WT\DownloadGateway\FileResolverRegistry;

class FileResolverTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		FileResolverRegistry::reset();
	}

	// -------------------------------------------------------------------------
	// DocumentFileResolver::resolve()
	// -------------------------------------------------------------------------

	public function test_resolve_returns_url_string_from_acf(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file', 42 ),
				'return' => 'https://example.com/uploads/doc.pdf',
			)
		);

		$resolver = new DocumentFileResolver();
		$this->assertSame( 'https://example.com/uploads/doc.pdf', $resolver->resolve( 42 ) );
	}

	public function test_resolve_extracts_url_from_acf_array_format(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file', 42 ),
				'return' => array(
					'url'      => 'https://example.com/uploads/doc.pdf',
					'filename' => 'doc.pdf',
				),
			)
		);

		$resolver = new DocumentFileResolver();
		$this->assertSame( 'https://example.com/uploads/doc.pdf', $resolver->resolve( 42 ) );
	}

	public function test_resolve_returns_null_when_field_is_empty(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file', 42 ),
				'return' => false,
			)
		);

		$resolver = new DocumentFileResolver();
		$this->assertNull( $resolver->resolve( 42 ) );
	}

	public function test_resolve_returns_null_when_array_has_no_url_key(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file', 42 ),
				'return' => array( 'filename' => 'doc.pdf' ), // malformed — no 'url' key
			)
		);

		$resolver = new DocumentFileResolver();
		$this->assertNull( $resolver->resolve( 42 ) );
	}

	public function test_storage_type_is_media(): void {
		$resolver = new DocumentFileResolver();
		$this->assertSame( 'media', $resolver->storage_type() );
	}

	// -------------------------------------------------------------------------
	// FileResolverRegistry
	// -------------------------------------------------------------------------

	public function test_has_resolver_returns_false_for_unregistered_type(): void {
		$this->assertFalse( FileResolverRegistry::has_resolver( 'document_files' ) );
	}

	public function test_has_resolver_returns_true_after_registering(): void {
		FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );
		$this->assertTrue( FileResolverRegistry::has_resolver( 'document_files' ) );
	}

	public function test_for_post_returns_null_when_post_not_found(): void {
		WP_Mock::userFunction(
			'get_post_type',
			array(
				'args'   => array( 99 ),
				'return' => false,
			)
		);

		$this->assertNull( FileResolverRegistry::for_post( 99 ) );
	}

	public function test_for_post_returns_null_when_no_resolver_for_post_type(): void {
		WP_Mock::userFunction(
			'get_post_type',
			array(
				'args'   => array( 42 ),
				'return' => 'videos',
			)
		);

		$this->assertNull( FileResolverRegistry::for_post( 42 ) );
	}

	public function test_for_post_returns_registered_resolver(): void {
		$resolver = new DocumentFileResolver();
		FileResolverRegistry::register( 'document_files', $resolver );

		WP_Mock::userFunction(
			'get_post_type',
			array(
				'args'   => array( 42 ),
				'return' => 'document_files',
			)
		);

		$this->assertSame( $resolver, FileResolverRegistry::for_post( 42 ) );
	}

	public function test_reset_clears_all_resolvers(): void {
		FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );
		FileResolverRegistry::reset();
		$this->assertFalse( FileResolverRegistry::has_resolver( 'document_files' ) );
	}
}
