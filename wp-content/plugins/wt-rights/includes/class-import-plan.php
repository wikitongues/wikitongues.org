<?php
/**
 * Import_Plan — what an import would change, worked out without WordPress.
 *
 * The importer reads the site's current reports, asks this class for a plan,
 * prints it, and applies it only with --execute. A second run plans nothing.
 *
 * Reports are matched by Airtable record ID, never by title or slug. A report
 * the release no longer contains is unpublished (set to draft), never deleted.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Plans an import.
 *
 * @phpstan-type Existing array{post_id: int, slug: string, title: string, status: string, hash: string, version: string, region: string, draft: bool}
 * @phpstan-type Action array{action: string, record_id: string, post_id: int, name: string, changes: list<string>, post_status: string}
 */
final class Import_Plan {

	/**
	 * The actions an import of $release would take.
	 *
	 * @param array<string, mixed>    $release         A release file.
	 * @param array<string, Existing> $existing        The site's reports, by record ID.
	 * @param array<string, string>   $region_statuses Region key → release status on this site.
	 * @return list<Action>
	 */
	public static function plan( array $release, array $existing, array $region_statuses ): array {
		$actions = array();
		$seen    = array();

		foreach ( (array) $release['reports'] as $report ) {
			$record_id          = (string) $report['record_id'];
			$seen[ $record_id ] = true;
			$post_status        = Release_Status::post_status( $region_statuses[ $report['region'] ] ?? Release_Status::UPCOMING, ! empty( $report['draft'] ) );
			$current            = $existing[ $record_id ] ?? null;

			if ( null === $current ) {
				$actions[] = self::action( 'create', $record_id, 0, (string) $report['name'], array(), $post_status );
				continue;
			}

			$changes = array();
			$compare = array(
				'title'  => array( $current['title'], (string) $report['name'] ),
				'slug'   => array( $current['slug'], (string) $report['slug'] ),
				'region' => array( $current['region'], (string) $report['region'] ),
				'status' => array( $current['status'], $post_status ),
				'hash'   => array( $current['hash'], (string) $report['hash'] ),
			);
			foreach ( $compare as $what => [ $was, $now ] ) {
				if ( $was !== $now ) {
					$changes[] = 'hash' === $what ? 'content' : $what . ' ' . $was . ' → ' . $now;
				}
			}
			if ( $current['draft'] !== ! empty( $report['draft'] ) ) {
				$changes[] = empty( $report['draft'] ) ? 'no longer a preview draft' : 'now a preview draft';
			}
			if ( $current['version'] !== (string) $report['version'] ) {
				$changes[] = 'version ' . $current['version'] . ' → ' . $report['version'];
			}
			$actions[] = self::action( $changes ? 'update' : 'unchanged', $record_id, $current['post_id'], (string) $report['name'], $changes, $post_status );
		}

		foreach ( $existing as $record_id => $current ) {
			if ( isset( $seen[ $record_id ] ) || 'draft' === $current['status'] ) {
				continue;
			}
			$actions[] = self::action( 'unpublish', (string) $record_id, $current['post_id'], $current['title'], array( 'not in this release' ), 'draft' );
		}
		return $actions;
	}

	/**
	 * Action counts by kind.
	 *
	 * @param list<Action> $actions A plan.
	 * @return array<string, int>
	 */
	public static function summary( array $actions ): array {
		$counts = array_fill_keys( array( 'create', 'update', 'unpublish', 'unchanged' ), 0 );
		foreach ( $actions as $action ) {
			++$counts[ $action['action'] ];
		}
		return $counts;
	}

	/**
	 * One action.
	 *
	 * @param string       $action      create, update, unchanged or unpublish.
	 * @param string       $record_id   Reports record ID.
	 * @param int          $post_id     The post, or 0 when it doesn't exist yet.
	 * @param string       $name        For the printed plan.
	 * @param list<string> $changes     What changes, for the printed plan.
	 * @param string       $post_status The post status after the import.
	 * @return Action
	 */
	private static function action( string $action, string $record_id, int $post_id, string $name, array $changes, string $post_status ): array {
		return array(
			'action'      => $action,
			'record_id'   => $record_id,
			'post_id'     => $post_id,
			'name'        => $name,
			'changes'     => $changes,
			'post_status' => $post_status,
		);
	}
}
