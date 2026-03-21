<?php
/**
 * VideoFileResolver — resolves file URLs for videos posts via Dropbox.
 *
 * Reads the ACF `dropbox_link` field and delegates to DropboxAdapter to obtain
 * a 4-hour temporary download URL. Returns null if the field is empty or if
 * the adapter cannot produce a link.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class VideoFileResolver implements FileResolver {

	private DropboxAdapter $adapter;

	public function __construct( ?DropboxAdapter $adapter = null ) {
		$this->adapter = $adapter ?? new DropboxAdapter();
	}

	public function resolve( int $post_id ): ?string {
		$shared_url = get_field( 'dropbox_link', $post_id );

		if ( empty( $shared_url ) || ! is_string( $shared_url ) ) {
			Logger::debug( "VideoFileResolver: no dropbox_link for post {$post_id}." );
			return null;
		}

		$url = $this->adapter->get_temporary_link( $shared_url );
		if ( null === $url ) {
			Logger::error( "VideoFileResolver: could not get temporary link for post {$post_id}." );
		}
		return $url;
	}

	public function storage_type(): string {
		return 'dropbox';
	}
}
