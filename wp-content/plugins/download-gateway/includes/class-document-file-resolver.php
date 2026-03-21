<?php
/**
 * DocumentFileResolver — resolves file URLs for document_files posts.
 *
 * Reads the ACF 'file' field, which is configured to return a URL string.
 * Handles the array return format defensively in case the field configuration
 * is changed.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class DocumentFileResolver implements FileResolver {

	public function resolve( int $post_id ): ?string {
		$value = get_field( 'file', $post_id );

		if ( empty( $value ) ) {
			Logger::debug( "DocumentFileResolver: no file field value for post {$post_id}." );
			return null;
		}

		// ACF 'file' field is configured to return URL, but handle array format defensively.
		if ( is_array( $value ) ) {
			$url = $value['url'] ?? null;
		} else {
			$url = (string) $value;
		}

		if ( empty( $url ) ) {
			Logger::error( "DocumentFileResolver: could not extract URL for post {$post_id}." );
			return null;
		}

		return $url;
	}

	public function storage_type(): string {
		return 'media';
	}
}
