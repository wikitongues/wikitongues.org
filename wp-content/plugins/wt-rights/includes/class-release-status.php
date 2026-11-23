<?php
/**
 * Release_Status — a region's release status, and the post status it gives
 * its reports.
 *
 * WordPress's own post statuses do the embargo, so core keeps unreleased
 * reports out of the sitemap, search, feeds and REST for anyone who can't
 * read them:
 *
 *   upcoming   draft    admins only, as a preview
 *   embargoed  private  admins and the press role
 *   released   publish  everyone
 *
 * A report exported with --preview stays a draft whatever its region's status.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Release statuses and their post statuses.
 */
final class Release_Status {

	public const UPCOMING  = 'upcoming';
	public const EMBARGOED = 'embargoed';
	public const RELEASED  = 'released';

	private const POST_STATUS = array(
		self::UPCOMING  => 'draft',
		self::EMBARGOED => 'private',
		self::RELEASED  => 'publish',
	);

	/**
	 * Every release status, in order.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		return array_keys( self::POST_STATUS );
	}

	/**
	 * Whether a value is a release status.
	 *
	 * @param string $status A value.
	 */
	public static function is_valid( string $status ): bool {
		return isset( self::POST_STATUS[ $status ] );
	}

	/**
	 * The post status for a report.
	 *
	 * @param string $region_status The region's release status; anything unknown counts as upcoming.
	 * @param bool   $draft         Whether the report was exported as a preview draft.
	 */
	public static function post_status( string $region_status, bool $draft ): string {
		if ( $draft ) {
			return 'draft';
		}
		return self::POST_STATUS[ $region_status ] ?? 'draft';
	}

	/**
	 * A region's change log with one more entry, newest first, at most $max long.
	 *
	 * @param list<array{at: string, by: string, from: string, to: string}> $log   The stored log.
	 * @param array{at: string, by: string, from: string, to: string}       $entry The change.
	 * @param int                                                           $max   Entries kept.
	 * @return list<array{at: string, by: string, from: string, to: string}>
	 */
	public static function log( array $log, array $entry, int $max = 20 ): array {
		array_unshift( $log, $entry );
		return array_slice( $log, 0, $max );
	}
}
