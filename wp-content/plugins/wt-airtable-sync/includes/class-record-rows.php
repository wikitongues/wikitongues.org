<?php
/**
 * Record_Rows — builds ACF repeater rows from parallel Airtable lists.
 *
 * Airtable linked records reach Make as an array of record IDs, and lookups of
 * the linked records' fields as arrays in the same order. Make posts those
 * arrays side by side; this class zips them into one row per linked record,
 * keyed by its record ID:
 *
 *   subdivisions            => [ 'recA', 'recB' ]
 *   subdivision_identifiers => [ 'Guam_US', 'Northern-Mariana-Islands_US' ]
 *
 *   → [ [ 'airtable_id' => 'recA', 'identifier' => 'Guam_US' ], ... ]
 *
 * The record ID is the row's identity; the other columns are labels that may
 * go stale and are refreshed on every sync. Rows are rebuilt from scratch on
 * each write, so the same payload always produces the same rows.
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class Record_Rows {

	/**
	 * Build repeater rows from the payload.
	 *
	 * The first column is the key column and must hold record IDs; a payload
	 * without it returns null, meaning "leave the field alone". Values that are
	 * not record IDs are dropped, as are repeats of an ID already seen.
	 *
	 * Label columns are matched to IDs by position. Send them as arrays: a
	 * string is treated as a single label, because Airtable names may contain
	 * commas. A missing or short label column leaves those labels empty.
	 *
	 * @param array<string, string> $columns Sub-field name => payload key; the first is the key column.
	 * @param array<string, mixed>  $payload Full request payload.
	 * @return array<int, array<string, string>>|null Rows, or null when the key column is absent.
	 */
	public static function build( array $columns, array $payload ): ?array {
		$key_field   = (string) array_key_first( $columns );
		$key_payload = $columns[ $key_field ] ?? '';

		if ( '' === $key_field || ! array_key_exists( $key_payload, $payload ) ) {
			return null;
		}

		$ids = self::to_list( $payload[ $key_payload ], true );

		$labels = array();
		foreach ( $columns as $sub_field => $payload_key ) {
			if ( $sub_field === $key_field ) {
				continue;
			}
			$labels[ $sub_field ] = self::to_list( $payload[ $payload_key ] ?? array(), false );
		}

		$rows = array();
		$seen = array();

		foreach ( $ids as $position => $id ) {
			if ( ! Field_Resolver::is_record_id( $id ) || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;

			$row = array( $key_field => $id );
			foreach ( $labels as $sub_field => $values ) {
				$row[ $sub_field ] = $values[ $position ] ?? '';
			}
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Normalise a payload value to a list of trimmed strings, keeping positions.
	 *
	 * @param mixed $value      Raw payload value.
	 * @param bool  $split_text Whether a string holds a comma-separated list (safe for record IDs only).
	 * @return array<int, string>
	 */
	private static function to_list( mixed $value, bool $split_text ): array {
		if ( null === $value || '' === $value ) {
			return array();
		}

		if ( is_array( $value ) ) {
			$items = array_values( $value );
		} elseif ( $split_text ) {
			$items = explode( ',', (string) $value );
		} else {
			$items = array( (string) $value );
		}

		return array_map(
			static fn( $item ): string => trim( str_replace( "\xc2\xa0", ' ', is_scalar( $item ) ? (string) $item : '' ) ),
			$items
		);
	}
}
