<?php
/**
 * Versioner — each report's own version number and changelog.
 *
 * The number is worked out, never typed. A report is compared with its entry
 * in the previous release:
 *
 *   not in the previous release            1.0
 *   its region isn't published yet         1.0, whatever changed
 *   content unchanged                      the same version
 *   text changed, every answer the same    minor: 1.0 → 1.1
 *   any indicator or rating changed        major: 1.x → 2.0
 *
 * A published report that changed needs at least one Changes note since the
 * previous release, or the release fails. A note with no change is only a
 * warning: a note for an edit that was later reverted shouldn't block a launch.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Numbers versions and builds changelogs.
 *
 * @phpstan-import-type Problem from Doc_Parser
 * @phpstan-type Entry array{version: string, date: string, notes: list<string>, changed_answers: list<string>}
 */
final class Versioner {

	/**
	 * A stable hash of a report's content: keys sorted at every level, so the
	 * same content always hashes the same, whatever order it was built in.
	 *
	 * @param array<mixed> $content Everything shown to readers, without the version or changelog.
	 */
	public static function hash( array $content ): string {
		// Unescaped slashes and Unicode, so the hash doesn't depend on how a URL or a name is escaped.
		return sha1( (string) json_encode( self::sorted( $content ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Pure, testable without WordPress; feeds a hash only.
	}

	/**
	 * The next version.
	 *
	 * @param array{version: string, hash: string, answers: array<string, string>}|null $previous  The report in the previous release, or null.
	 * @param string                                                                     $hash      This content's hash.
	 * @param array<string, string>                                                      $answers   This release's answers by key.
	 * @param bool                                                                       $published Whether the report's region release date has passed.
	 * @param list<string>                                                               $notes     Changes notes dated since the previous release.
	 * @return array{version: string, changed: bool, changed_answers: list<string>, problems: list<Problem>}
	 */
	public static function next( ?array $previous, string $hash, array $answers, bool $published, array $notes ): array {
		$result = array(
			'version'         => '1.0',
			'changed'         => false,
			'changed_answers' => array(),
			'problems'        => array(),
		);
		if ( null === $previous || ! $published ) {
			return $result;
		}

		$result['version'] = $previous['version'];
		if ( $previous['hash'] === $hash ) {
			if ( $notes ) {
				$result['problems'][] = array(
					'code'     => 'note_without_change',
					'severity' => 'warning',
					'message'  => 'There is a Changes note, but the report did not change.',
				);
			}
			return $result;
		}

		$changed = array();
		foreach ( $answers as $key => $answer ) {
			if ( ( $previous['answers'][ $key ] ?? '' ) !== $answer ) {
				$changed[] = $key;
			}
		}

		[ $major, $minor ]         = array_map( 'intval', explode( '.', $previous['version'] . '.0' ) );
		$result['version']         = $changed ? ( $major + 1 ) . '.0' : $major . '.' . ( $minor + 1 );
		$result['changed']         = true;
		$result['changed_answers'] = $changed;

		if ( ! $notes ) {
			$result['problems'][] = array(
				'code'     => 'missing_change_note',
				'severity' => 'error',
				'message'  => 'The report changed after publication but has no Changes note since the last release.',
			);
		}
		return $result;
	}

	/**
	 * The changelog with this release's entry added, newest first. Unchanged
	 * when the version didn't change.
	 *
	 * @param list<Entry>  $changelog       The previous release's changelog.
	 * @param string       $version         This release's version.
	 * @param string       $date            The release date, Y-m-d.
	 * @param list<string> $notes           The Changes notes.
	 * @param list<string> $changed_answers Answer keys that changed.
	 * @return list<Entry>
	 */
	public static function changelog( array $changelog, string $version, string $date, array $notes, array $changed_answers ): array {
		if ( $changelog && $changelog[0]['version'] === $version ) {
			return $changelog;
		}
		array_unshift(
			$changelog,
			array(
				'version'         => $version,
				'date'            => $date,
				'notes'           => $notes,
				'changed_answers' => $changed_answers,
			)
		);
		return $changelog;
	}

	/**
	 * An array with its string keys sorted at every level; lists keep their order.
	 *
	 * @param mixed $value Any value.
	 * @return mixed
	 */
	private static function sorted( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( ! array_is_list( $value ) ) {
			ksort( $value );
		}
		return array_map( array( self::class, 'sorted' ), $value );
	}
}
