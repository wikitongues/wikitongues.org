<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\CaptionFileResolver;
use WT\DownloadGateway\DropboxAdapter;

class CaptionFileResolverTest extends TestCase {

	private const POST_ID  = 77;
	private const TEMP_URL = 'https://dl.dropboxusercontent.com/apitl/1/test-caption-link';

	public function test_resolve_returns_null_when_field_empty(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file_url', self::POST_ID ),
				'return' => false,
			)
		);

		$resolver = new CaptionFileResolver();
		$this->assertNull( $resolver->resolve( self::POST_ID ) );
	}

	public function test_resolve_returns_null_when_adapter_fails(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file_url', self::POST_ID ),
				'return' => 'https://www.dropbox.com/sh/abc/caption.srt?dl=0',
			)
		);

		/** @var \Mockery\MockInterface&DropboxAdapter $adapter */
		$adapter = Mockery::mock( DropboxAdapter::class );
		$adapter->shouldReceive( 'get_temporary_link' )->andReturn( null );

		$resolver = new CaptionFileResolver( $adapter );
		$this->assertNull( $resolver->resolve( self::POST_ID ) );
	}

	public function test_resolve_returns_temporary_link_on_success(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'file_url', self::POST_ID ),
				'return' => 'https://www.dropbox.com/sh/abc/caption.srt?dl=0',
			)
		);

		/** @var \Mockery\MockInterface&DropboxAdapter $adapter */
		$adapter = Mockery::mock( DropboxAdapter::class );
		$adapter->shouldReceive( 'get_temporary_link' )->andReturn( self::TEMP_URL );

		$resolver = new CaptionFileResolver( $adapter );
		$this->assertSame( self::TEMP_URL, $resolver->resolve( self::POST_ID ) );
	}

	public function test_storage_type_is_dropbox(): void {
		$resolver = new CaptionFileResolver();
		$this->assertSame( 'dropbox', $resolver->storage_type() );
	}
}
