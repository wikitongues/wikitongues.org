<?php
/**
 * CaptionFileResolver — resolves file URLs for captions posts via Dropbox.
 *
 * Reads the ACF `file_url` field and delegates to DropboxAdapter to obtain
 * a 4-hour temporary download URL. Returns null if the field is empty or if
 * the adapter cannot produce a link.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class CaptionFileResolver implements FileResolver {

	private DropboxAdapter $adapter;

	public function __construct( ?DropboxAdapter $adapter = null ) {
		$this->adapter = $adapter ?? new DropboxAdapter();
	}

	public function resolve( int $post_id ): ?string {
		$shared_url = get_field( 'file_url', $post_id );

		if ( empty( $shared_url ) || ! is_string( $shared_url ) ) {
			Logger::debug( "CaptionFileResolver: no file_url for post {$post_id}." );
			return null;
		}

		$url = $this->adapter->get_temporary_link( $shared_url );
		if ( null === $url ) {
			Logger::error( "CaptionFileResolver: could not get temporary link for post {$post_id}." );
		}
		return $url;
	}

	public function storage_type(): string {
		return 'dropbox';
	}
}
