<?php

use WP_Mock\Tools\TestCase;
use WT\DownloadGateway\DropboxAdapter;

class DropboxAdapterTest extends TestCase {

	private const SHARED_URL = 'https://www.dropbox.com/sh/abc123/test-video.mp4?dl=0';
	private const FILE_PATH  = '/videos/test-video.mp4';
	private const TEMP_LINK  = 'https://dl.dropboxusercontent.com/apitl/1/test-temp-link';
	private const TOKEN      = 'sl.test-access-token';

	// -------------------------------------------------------------------------
	// Missing credentials
	// -------------------------------------------------------------------------

	public function test_get_temporary_link_returns_null_when_credentials_missing(): void {
		$adapter = new DropboxAdapter( '', '', '' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertNull( $result );
	}

	// -------------------------------------------------------------------------
	// Access token caching
	// -------------------------------------------------------------------------

	public function test_get_temporary_link_uses_cached_access_token(): void {
		// All three transients hit — no wp_remote_post calls needed.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN ),
				'return' => self::TOKEN,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_path_' . md5( self::SHARED_URL ) ),
				'return' => self::FILE_PATH,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_link_' . md5( self::FILE_PATH ) ),
				'return' => self::TEMP_LINK,
			)
		);

		$adapter = new DropboxAdapter( 'key', 'secret', 'refresh' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertSame( self::TEMP_LINK, $result );
	}

	public function test_get_temporary_link_refreshes_access_token_when_expired(): void {
		// Access token transient miss → refresh succeeds → path and link hit.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN ),
				'return' => false,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_path_' . md5( self::SHARED_URL ) ),
				'return' => self::FILE_PATH,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_link_' . md5( self::FILE_PATH ) ),
				'return' => self::TEMP_LINK,
			)
		);

		$token_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'access_token' => self::TOKEN ) ),
		);
		WP_Mock::userFunction( 'wp_remote_post', array( 'return' => $token_response ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 200 ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_body', array( 'return' => json_encode( array( 'access_token' => self::TOKEN ) ) ) );
		WP_Mock::userFunction(
			'set_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN, self::TOKEN, (int) round( 3.5 * HOUR_IN_SECONDS ) ),
				'return' => true,
			)
		);

		$adapter = new DropboxAdapter( 'key', 'secret', 'refresh' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertSame( self::TEMP_LINK, $result );
	}

	// -------------------------------------------------------------------------
	// File path caching
	// -------------------------------------------------------------------------

	public function test_get_temporary_link_uses_cached_path(): void {
		// Token and path hit → link miss → calls files/get_temporary_link only.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN ),
				'return' => self::TOKEN,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_path_' . md5( self::SHARED_URL ) ),
				'return' => self::FILE_PATH,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_link_' . md5( self::FILE_PATH ) ),
				'return' => false,
			)
		);

		$link_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'link' => self::TEMP_LINK ) ),
		);
		WP_Mock::userFunction(
			'wp_remote_post',
			array(
				'times'  => 1,
				'return' => $link_response,
			)
		);
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 200 ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_body', array( 'return' => json_encode( array( 'link' => self::TEMP_LINK ) ) ) );
		WP_Mock::userFunction( 'set_transient', array( 'return' => true ) );

		$adapter = new DropboxAdapter( 'key', 'secret', 'refresh' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertSame( self::TEMP_LINK, $result );
	}

	public function test_get_temporary_link_fetches_path_on_cache_miss(): void {
		// Token hit → path miss → calls metadata API → caches path 7 days → link hit.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN ),
				'return' => self::TOKEN,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_path_' . md5( self::SHARED_URL ) ),
				'return' => false,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_link_' . md5( self::FILE_PATH ) ),
				'return' => self::TEMP_LINK,
			)
		);

		$path_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'path_lower' => self::FILE_PATH ) ),
		);
		WP_Mock::userFunction(
			'wp_remote_post',
			array(
				'times'  => 1,
				'return' => $path_response,
			)
		);
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 200 ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_body', array( 'return' => json_encode( array( 'path_lower' => self::FILE_PATH ) ) ) );
		WP_Mock::userFunction(
			'set_transient',
			array(
				'args'   => array( 'gateway_dbx_path_' . md5( self::SHARED_URL ), self::FILE_PATH, 7 * DAY_IN_SECONDS ),
				'return' => true,
			)
		);

		$adapter = new DropboxAdapter( 'key', 'secret', 'refresh' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertSame( self::TEMP_LINK, $result );
	}

	// -------------------------------------------------------------------------
	// Temp link caching
	// -------------------------------------------------------------------------

	public function test_get_temporary_link_uses_cached_link(): void {
		// All three transients hit — files/get_temporary_link is not called.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN ),
				'return' => self::TOKEN,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_path_' . md5( self::SHARED_URL ) ),
				'return' => self::FILE_PATH,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_link_' . md5( self::FILE_PATH ) ),
				'return' => self::TEMP_LINK,
			)
		);

		$adapter = new DropboxAdapter( 'key', 'secret', 'refresh' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertSame( self::TEMP_LINK, $result );
	}

	// -------------------------------------------------------------------------
	// Happy path
	// -------------------------------------------------------------------------

	public function test_get_temporary_link_returns_link_on_success(): void {
		// Token hit, path hit, link miss → returns new temp link from API.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN ),
				'return' => self::TOKEN,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_path_' . md5( self::SHARED_URL ) ),
				'return' => self::FILE_PATH,
			)
		);
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'gateway_dbx_link_' . md5( self::FILE_PATH ) ),
				'return' => false,
			)
		);

		$link_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'link' => self::TEMP_LINK ) ),
		);
		WP_Mock::userFunction( 'wp_remote_post', array( 'return' => $link_response ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 200 ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_body', array( 'return' => json_encode( array( 'link' => self::TEMP_LINK ) ) ) );
		WP_Mock::userFunction( 'set_transient', array( 'return' => true ) );

		$adapter = new DropboxAdapter( 'key', 'secret', 'refresh' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertSame( self::TEMP_LINK, $result );
	}

	// -------------------------------------------------------------------------
	// Error handling
	// -------------------------------------------------------------------------

	public function test_get_temporary_link_returns_null_on_api_error(): void {
		// Token transient miss → wp_remote_post returns WP_Error.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN ),
				'return' => false,
			)
		);
		WP_Mock::userFunction(
			'wp_remote_post',
			array( 'return' => new WP_Error( 'http_error', 'Connection failed.' ) )
		);

		$adapter = new DropboxAdapter( 'key', 'secret', 'refresh' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertNull( $result );
	}

	public function test_get_temporary_link_returns_null_on_non_200_response(): void {
		// Token transient miss → wp_remote_post returns 401.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( DropboxAdapter::TRANSIENT_ACCESS_TOKEN ),
				'return' => false,
			)
		);
		WP_Mock::userFunction( 'wp_remote_post', array( 'return' => array( 'response' => array( 'code' => 401 ) ) ) );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'return' => 401 ) );

		$adapter = new DropboxAdapter( 'key', 'secret', 'refresh' );
		$result  = $adapter->get_temporary_link( self::SHARED_URL );
		$this->assertNull( $result );
	}
}
