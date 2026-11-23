<?php
/**
 * Validator — a report's Airtable row against its parsed Doc, and a region's
 * readiness to release.
 *
 * Airtable decides the answers and the Doc decides the text, so the two must
 * agree. The Airtable row reaches this class already mapped to plain values:
 *
 *   record_id  The Reports record ID.
 *   name       The country, for messages.
 *   status     The Research Status option name, as Kristen wrote it.
 *   answers    Indicator and rating key → the option name (any case).
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Checks reports and regions.
 *
 * @phpstan-import-type Problem from Doc_Parser
 */
final class Validator {

	/**
	 * The structure, from wt_rights_structure().
	 *
	 * @var array<string, mixed>
	 */
	private array $structure;

	/**
	 * Builds a validator for one structure.
	 *
	 * @param array<string, mixed> $structure From wt_rights_structure().
	 */
	public function __construct( array $structure ) {
		$this->structure = $structure;
	}

	/**
	 * What a Research Status means for a release: publish, held, not_started,
	 * blocking, or unknown for an option this code doesn't know.
	 *
	 * @param string $status The option name.
	 */
	public function status_role( string $status ): string {
		foreach ( $this->structure['statuses'] as $name => $role ) {
			if ( Doc_Parser::fold( $name ) === Doc_Parser::fold( $status ) ) {
				return $role;
			}
		}
		return 'unknown';
	}

	/**
	 * Problems with one report: its Airtable answers, the Doc's own problems,
	 * and any answer on which the two disagree.
	 *
	 * @param array<string, mixed> $row    The mapped Airtable row.
	 * @param array<string, mixed> $parsed From Doc_Parser::parse().
	 * @return list<Problem>
	 */
	public function report( array $row, array $parsed ): array {
		$problems = $parsed['problems'] ?? array();

		foreach ( $this->answer_keys() as $key => $spec ) {
			$airtable = Answers::normalize( (string) ( $row['answers'][ $key ] ?? '' ) );
			if ( '' === $airtable ) {
				$problems[] = self::problem( 'airtable_missing_answer', $spec['label'] . ' is empty in Airtable.' );
				continue;
			}
			if ( ! in_array( $airtable, $spec['answers'], true ) ) {
				$problems[] = self::problem( 'airtable_invalid_answer', $spec['label'] . ': Airtable has "' . $airtable . '".' );
				continue;
			}
			$doc = $this->doc_answer( $parsed, $key );
			if ( '' !== $doc && $doc !== $airtable ) {
				$problems[] = self::problem( 'answer_mismatch', $spec['label'] . ': the Doc says "' . $doc . '", Airtable says "' . $airtable . '".' );
			}
		}
		return $problems;
	}

	/**
	 * Problems that stop a region being released: any country whose status
	 * blocks it, or whose status this code doesn't know.
	 *
	 * @param list<array<string, mixed>> $rows The region's mapped Airtable rows.
	 * @return list<Problem>
	 */
	public function region( array $rows ): array {
		$problems = array();
		foreach ( $rows as $row ) {
			$status = (string) ( $row['status'] ?? '' );
			$role   = $this->status_role( $status );
			if ( 'blocking' === $role ) {
				$problems[] = self::problem( 'region_blocked', $row['name'] . ' is "' . $status . '": set it to Completed or Held.' );
			} elseif ( 'unknown' === $role ) {
				$problems[] = self::problem( 'unknown_status', $row['name'] . ' has an unknown Research Status "' . $status . '".' );
			}
		}
		return $problems;
	}

	/**
	 * Whether a list of problems contains an error.
	 *
	 * @param list<Problem> $problems Problems.
	 */
	public static function has_errors( array $problems ): bool {
		foreach ( $problems as $problem ) {
			if ( 'error' === $problem['severity'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Every answer key (indicators, then ratings) with its label and allowed answers.
	 *
	 * @return array<string, array{label: string, answers: list<string>}>
	 */
	public function answer_keys(): array {
		$keys = array();
		foreach ( array_merge( $this->structure['indicators'], $this->structure['ratings'] ) as $item ) {
			$keys[ $item['key'] ] = array(
				'label'   => $item['label'],
				'answers' => $item['answers'],
			);
		}
		return $keys;
	}

	/**
	 * The Doc's answer for a key, or '' when it has none.
	 *
	 * @param array<string, mixed> $parsed From Doc_Parser::parse().
	 * @param string               $key    Indicator or rating key.
	 */
	private function doc_answer( array $parsed, string $key ): string {
		if ( isset( $parsed['dimensions'][ $key ] ) ) {
			return (string) $parsed['dimensions'][ $key ]['answer'];
		}
		return (string) ( $parsed['rating'][ $key ] ?? '' );
	}

	/**
	 * An error.
	 *
	 * @param string $code    Stable code.
	 * @param string $message For the editor.
	 * @return Problem
	 */
	private static function problem( string $code, string $message ): array {
		return array(
			'code'     => $code,
			'severity' => 'error',
			'message'  => $message,
		);
	}
}
