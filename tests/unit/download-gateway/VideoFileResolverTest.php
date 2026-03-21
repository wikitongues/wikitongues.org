<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\DropboxAdapter;
use WT\DownloadGateway\VideoFileResolver;

class VideoFileResolverTest extends TestCase {

	private const POST_ID  = 55;
	private const TEMP_URL = 'https://dl.dropboxusercontent.com/apitl/1/test-video-link';

	public function test_resolve_returns_null_when_field_empty(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'dropbox_link', self::POST_ID ),
				'return' => false,
			)
		);

		$resolver = new VideoFileResolver();
		$this->assertNull( $resolver->resolve( self::POST_ID ) );
	}

	public function test_resolve_returns_null_when_adapter_fails(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'dropbox_link', self::POST_ID ),
				'return' => 'https://www.dropbox.com/sh/abc/video.mp4?dl=0',
			)
		);

		/** @var \Mockery\MockInterface&DropboxAdapter $adapter */
		$adapter = Mockery::mock( DropboxAdapter::class );
		$adapter->shouldReceive( 'get_temporary_link' )->andReturn( null );

		$resolver = new VideoFileResolver( $adapter );
		$this->assertNull( $resolver->resolve( self::POST_ID ) );
	}

	public function test_resolve_returns_temporary_link_on_success(): void {
		WP_Mock::userFunction(
			'get_field',
			array(
				'args'   => array( 'dropbox_link', self::POST_ID ),
				'return' => 'https://www.dropbox.com/sh/abc/video.mp4?dl=0',
			)
		);

		/** @var \Mockery\MockInterface&DropboxAdapter $adapter */
		$adapter = Mockery::mock( DropboxAdapter::class );
		$adapter->shouldReceive( 'get_temporary_link' )->andReturn( self::TEMP_URL );

		$resolver = new VideoFileResolver( $adapter );
		$this->assertSame( self::TEMP_URL, $resolver->resolve( self::POST_ID ) );
	}

	public function test_storage_type_is_dropbox(): void {
		$resolver = new VideoFileResolver();
		$this->assertSame( 'dropbox', $resolver->storage_type() );
	}
}
