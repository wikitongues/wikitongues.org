<?php
/**
 * Pdf_Resolver — the download gateway's file lookup for country reports.
 *
 * Loaded only when the gateway is active. Returns the PDF only for a published
 * report, so an embargoed report has no download even by direct request: the
 * gateway answers 404.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

use WT\DownloadGateway\FileResolver;

/**
 * Resolves a report's PDF.
 */
final class Pdf_Resolver implements FileResolver {

	/**
	 * The PDF's URL, or null when the report isn't released or has none.
	 *
	 * @param int $post_id A rights_reports post.
	 */
	public function resolve( int $post_id ): ?string {
		if ( 'rights_reports' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return null;
		}
		$attachment = (int) get_post_meta( $post_id, '_rights_pdf_attachment', true );
		$url        = $attachment ? wp_get_attachment_url( $attachment ) : false;
		return $url ? $url : null;
	}

	/**
	 * Media library.
	 */
	public function storage_type(): string {
		return 'media';
	}
}
