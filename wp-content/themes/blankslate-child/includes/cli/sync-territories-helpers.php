<?php
/**
 * Pure planning logic for `wp wt sync-territories`.
 *
 * Kept free of WordPress calls so it can be unit tested.
 *
 * @package Wikitongues
 */

/**
 * Turn CSV rows into Territories payloads, reporting rows that can't be used.
 *
 * Expected columns: airtable_id, post_title, iso_3166_code, nation (Nations
 * record IDs, comma-separated). Places are keyed on the record ID, so a row
 * without one is rejected rather than matched by name.
 *
 * @param array<int, array<string, string>> $rows CSV rows keyed by header.
 * @return array{rows: array<int, array{airtable_id: string, post_title: string, iso_3166_code: string, nation: string}>, invalid: array<int, string>}
 */
function wt_territory_rows( array $rows ): array {
	$out  = array(
		'rows'    => array(),
		'invalid' => array(),
	);
	$seen = array();

	foreach ( $rows as $i => $row ) {
		$id = trim( (string) ( $row['airtable_id'] ?? '' ) );
		// Same cleanup WordPress applies when saving a title (sanitize_text_field),
		// so an unchanged row compares equal on the next run.
		$title = trim( (string) preg_replace( '/\s+/u', ' ', str_replace( "\xc2\xa0", ' ', (string) ( $row['post_title'] ?? '' ) ) ) );
		$line  = $i + 2;

		if ( ! preg_match( '/^rec[A-Za-z0-9]{14}$/', $id ) ) {
			$out['invalid'][] = sprintf( 'line %d: no Airtable record ID ("%s")', $line, $id );
			continue;
		}
		if ( '' === $title ) {
			$out['invalid'][] = sprintf( 'line %d: %s has no title', $line, $id );
			continue;
		}
		if ( isset( $seen[ $id ] ) ) {
			$out['invalid'][] = sprintf( 'line %d: %s repeats line %d', $line, $id, $seen[ $id ] );
			continue;
		}
		$seen[ $id ] = $line;

		$nations = array_values(
			array_filter(
				array_map( 'trim', explode( ',', (string) ( $row['nation'] ?? '' ) ) ),
				static fn( string $n ): bool => 1 === preg_match( '/^rec[A-Za-z0-9]{14}$/', $n )
			)
		);

		$out['rows'][] = array(
			'airtable_id'   => $id,
			'post_title'    => $title,
			'iso_3166_code' => strtoupper( trim( (string) ( $row['iso_3166_code'] ?? '' ) ) ),
			'nation'        => implode( ',', $nations ),
		);
	}

	return $out;
}

/**
 * Plan what syncing each row would change.
 *
 * Compares each row with the place WordPress already holds for its record
 * ID. Only the fields the sync writes are compared, and only the first
 * nation counts, because the place's `nation` field holds one. A row whose
 * record ID is unknown is a create, unless a post of its target type
 * already has its title: the sync then stamps that post (the transition
 * fallback for the territory pages made by hand).
 *
 * A nation that WordPress can't resolve (its Nations record is not stamped on
 * any post) is compared as empty, since the sync would write it as empty;
 * otherwise those rows would show as changed on every run.
 *
 * @param array<int, array{airtable_id: string, post_title: string, iso_3166_code: string, nation: string}> $rows    From wt_territory_rows().
 * @param array<string, array{type: string, title: string, iso: string, nation: string}>                  $current Record ID => what WordPress holds (nation as a record ID, '' if none).
 * @param array<string, array<string, int>>                                                                $titles  Post type => title => count of unstamped posts with that title.
 * @param array<string, bool>                                                                             $known   Record IDs stamped on any post (nations resolve only to these).
 * @return array{create: string[], stamp: string[], update: string[], type_change: string[], unchanged: int, ambiguous: string[], todo: string[]}
 */
function wt_plan_territory_sync( array $rows, array $current, array $titles, array $known ): array {
	$plan = array(
		'create'      => array(),
		'stamp'       => array(),
		'update'      => array(),
		'type_change' => array(),
		'unchanged'   => 0,
		'ambiguous'   => array(),
		'todo'        => array(),
	);

	foreach ( $rows as $row ) {
		$id     = $row['airtable_id'];
		$target = '' !== $row['iso_3166_code'] ? 'territories' : 'subdivisions';
		$nation = explode( ',', $row['nation'] )[0];
		$nation = isset( $known[ $nation ] ) ? $nation : '';
		$label  = sprintf( '%s (%s)', $row['post_title'], $id );

		if ( ! isset( $current[ $id ] ) ) {
			$matches = $titles[ $target ][ $row['post_title'] ] ?? 0;
			if ( 1 === $matches ) {
				$plan['stamp'][] = $label;
				$plan['todo'][]  = $id;
			} elseif ( $matches > 1 ) {
				$plan['ambiguous'][] = $label;
			} else {
				$plan['create'][] = $label . ' as ' . $target;
				$plan['todo'][]   = $id;
			}
			continue;
		}

		$have = $current[ $id ];
		if ( $have['type'] !== $target ) {
			$plan['type_change'][] = sprintf( '%s: %s → %s', $label, $have['type'], $target );
			$plan['todo'][]        = $id;
			continue;
		}

		$changed = array();
		if ( $have['title'] !== $row['post_title'] ) {
			$changed[] = 'title';
		}
		if ( $have['iso'] !== $row['iso_3166_code'] ) {
			$changed[] = 'ISO code';
		}
		if ( $have['nation'] !== $nation ) {
			$changed[] = 'nation';
		}

		if ( $changed ) {
			$plan['update'][] = $label . ': ' . implode( ', ', $changed );
			$plan['todo'][]   = $id;
		} else {
			++$plan['unchanged'];
		}
	}

	return $plan;
}
