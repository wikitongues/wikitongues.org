<?php
/**
 * FileResolver — contract for post-type-specific file URL resolution.
 *
 * Each CPT that participates in the gateway implements this interface.
 * The gateway's FileResolverRegistry maps post types to resolvers; the
 * download endpoint calls resolve() to get the file URL without knowing
 * anything about the underlying storage.
 *
 * Current implementations:
 *   DocumentFileResolver — resolves ACF 'file' field on document_files posts
 *
 * Planned:
 *   VideoResolver         — video/caption files (sub-phase 6)
 *   DropboxResolver       — Dropbox temporary link adapter (sub-phase 6)
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

interface FileResolver {

	/**
	 * Resolve the downloadable file URL for a given post.
	 *
	 * @param int $post_id Post ID of the downloadable item.
	 * @return string|null Absolute URL to the file, or null if unresolvable.
	 */
	public function resolve( int $post_id ): ?string;

	/**
	 * Return the storage type label for event logging.
	 *
	 * Must be one of: 'local', 'media', 'dropbox', 'external'.
	 *
	 * @return string
	 */
	public function storage_type(): string;
}
