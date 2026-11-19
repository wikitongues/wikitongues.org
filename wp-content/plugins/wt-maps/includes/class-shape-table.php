<?php
/**
 * Shape_Table — which Natural Earth shapes draw each place.
 *
 * The table is `data/shapes.json`, committed with the plugin and reviewed in
 * the same PR as the maps it builds. It is keyed on Airtable record IDs, which
 * every place carries in `_airtable_record_id` (nations, ISO-coded territories
 * and subdivisions alike) and which are the same on every environment, so the
 * table needs no database writes and no per-environment backfill.
 *
 *   places: record ID → { name, shapes: ["layer:CODE", …], note? }
 *   focus:  language record ID → { name, places: [place record IDs] }
 *
 * `name` is a label for people reading the file; nothing matches on it.
 *
 * A shape reference names a Natural Earth layer and that layer's code:
 *
 *   countries  ADM0_A3 in admin_0_countries          (AFG)
 *   units      GU_A3   in admin_0_map_units          (FXX, GAZ, GUM)
 *   subunits   SU_A3   in admin_0_map_subunits       (USH, WLS)
 *   breakaway  BRK_A3  in admin_0_breakaway_disputed (B35)
 *
 * A place with several references is drawn as all of them (Palestine is
 * `units:GAZ` and `units:WEB`). A place missing from the table has no map,
 * never a wrong one.
 *
 * `focus` is the curated launch list for maps that draw part of a nation: the
 * listed places are drawn for that language in place of its territory links
 * (Hawaiian → Hawaii, not the whole United States). It is a list, not a rule
 * over every language's Airtable territories, because those still mix origin
 * with official-language links until plan 6.8 phase C separates them.
 *
 * Pure: no WordPress calls, so it is unit-tested directly.
 *
 * @package WT\Maps
 */

namespace WT\Maps;

final class Shape_Table {

	/**
	 * Natural Earth layers a shape reference may name.
	 */
	public const LAYERS = array( 'countries', 'units', 'subunits', 'breakaway' );

	/**
	 * Place record ID → shape references.
	 *
	 * @var array<string, list<string>>
	 */
	private array $places = array();

	/**
	 * Place record ID → label.
	 *
	 * @var array<string, string>
	 */
	private array $names = array();

	/**
	 * Language record ID → place record IDs.
	 *
	 * @var array<string, list<string>>
	 */
	private array $focus = array();

	/**
	 * Problems found while reading the table.
	 *
	 * @var list<string>
	 */
	private array $problems = array();

	private string $version = '';

	/**
	 * Read the table from a JSON file.
	 *
	 * A missing or unreadable file gives an empty table that reports the
	 * problem, so a broken file shows no maps rather than a fatal error.
	 *
	 * @param string $path Path to shapes.json.
	 * @return self
	 */
	public static function from_file( string $path ): self {
		$json = is_readable( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = false === $json ? null : json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			$table             = new self();
			$table->problems[] = sprintf( 'Cannot read the shape table at %s.', $path );
			return $table;
		}

		return self::from_array( $data );
	}

	/**
	 * Build the table from decoded JSON, keeping only valid entries.
	 *
	 * @param array<string, mixed> $data Decoded shapes.json.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$table          = new self();
		$table->version = is_string( $data['natural_earth'] ?? null ) ? $data['natural_earth'] : '';

		if ( '' === $table->version ) {
			$table->problems[] = 'The table does not say which Natural Earth version its codes come from.';
		}

		$places = is_array( $data['places'] ?? null ) ? $data['places'] : array();
		foreach ( $places as $record_id => $entry ) {
			$table->add_place( (string) $record_id, is_array( $entry ) ? $entry : array() );
		}

		$focus = is_array( $data['focus'] ?? null ) ? $data['focus'] : array();
		foreach ( $focus as $language_id => $entry ) {
			$table->add_focus( (string) $language_id, is_array( $entry ) ? $entry : array() );
		}

		return $table;
	}

	/**
	 * Whether a string is an Airtable record ID.
	 *
	 * @param string $value Candidate ID.
	 * @return bool
	 */
	public static function is_record_id( string $value ): bool {
		return 1 === preg_match( '/^rec[A-Za-z0-9]{14}$/', $value );
	}

	/**
	 * Split a shape reference into its layer and code.
	 *
	 * @param string $ref A reference such as `units:GAZ`.
	 * @return array{layer: string, code: string}|null Null when malformed.
	 */
	public static function parse_ref( string $ref ): ?array {
		if ( 1 !== preg_match( '/^([a-z]+):([A-Z0-9]{3})$/', $ref, $m ) || ! in_array( $m[1], self::LAYERS, true ) ) {
			return null;
		}

		return array(
			'layer' => $m[1],
			'code'  => $m[2],
		);
	}

	/**
	 * Natural Earth version the codes come from.
	 *
	 * @return string
	 */
	public function natural_earth_version(): string {
		return $this->version;
	}

	/**
	 * Shape references for a place, or an empty list when it has none.
	 *
	 * @param string $record_id Place record ID.
	 * @return list<string>
	 */
	public function shapes( string $record_id ): array {
		return $this->places[ $record_id ] ?? array();
	}

	/**
	 * Label of a place in the table.
	 *
	 * @param string $record_id Place record ID.
	 * @return string
	 */
	public function name( string $record_id ): string {
		return $this->names[ $record_id ] ?? '';
	}

	/**
	 * Record IDs of every place in the table.
	 *
	 * @return list<string>
	 */
	public function place_ids(): array {
		return array_keys( $this->places );
	}

	/**
	 * Places a language's map focuses on, or an empty list for none.
	 *
	 * @param string $language_record_id Language record ID.
	 * @return list<string>
	 */
	public function focus( string $language_record_id ): array {
		return $this->focus[ $language_record_id ] ?? array();
	}

	/**
	 * Record IDs of every language with a focus.
	 *
	 * @return list<string>
	 */
	public function focus_language_ids(): array {
		return array_keys( $this->focus );
	}

	/**
	 * Problems found while reading the table; empty when it is clean.
	 *
	 * @return list<string>
	 */
	public function problems(): array {
		return $this->problems;
	}

	/**
	 * Add one place entry, or record why it was left out.
	 *
	 * @param string               $record_id Key from the file.
	 * @param array<string, mixed> $entry     The place's entry.
	 */
	private function add_place( string $record_id, array $entry ): void {
		$label = is_string( $entry['name'] ?? null ) ? $entry['name'] : '';
		$where = '' !== $label ? sprintf( '%s (%s)', $label, $record_id ) : $record_id;

		if ( ! self::is_record_id( $record_id ) ) {
			$this->problems[] = sprintf( 'Place key %s is not an Airtable record ID.', $where );
			return;
		}

		$shapes = array();
		foreach ( (array) ( $entry['shapes'] ?? array() ) as $ref ) {
			$ref = is_string( $ref ) ? $ref : '';
			if ( null === self::parse_ref( $ref ) ) {
				$this->problems[] = sprintf( 'Place %s has a malformed shape reference "%s"; use layer:CODE with a layer from %s.', $where, $ref, implode( ', ', self::LAYERS ) );
				continue;
			}
			$shapes[] = $ref;
		}

		$shapes = array_values( array_unique( $shapes ) );
		if ( array() === $shapes ) {
			$this->problems[] = sprintf( 'Place %s has no shapes; leave it out of the table instead.', $where );
			return;
		}

		$this->places[ $record_id ] = $shapes;
		$this->names[ $record_id ]  = $label;
	}

	/**
	 * Add one focus entry, or record why it was left out.
	 *
	 * Runs after every place is read, so it can check its places have shapes.
	 *
	 * @param string               $language_id Key from the file.
	 * @param array<string, mixed> $entry       The focus entry.
	 */
	private function add_focus( string $language_id, array $entry ): void {
		$label = is_string( $entry['name'] ?? null ) ? $entry['name'] : '';
		$where = '' !== $label ? sprintf( '%s (%s)', $label, $language_id ) : $language_id;

		if ( ! self::is_record_id( $language_id ) ) {
			$this->problems[] = sprintf( 'Focus key %s is not an Airtable record ID.', $where );
			return;
		}

		$places = array();
		foreach ( (array) ( $entry['places'] ?? array() ) as $place_id ) {
			$place_id = is_string( $place_id ) ? $place_id : '';
			if ( ! isset( $this->places[ $place_id ] ) ) {
				$this->problems[] = sprintf( 'Focus for %s names place "%s", which has no shapes in the table.', $where, $place_id );
				continue;
			}
			$places[] = $place_id;
		}

		$places = array_values( array_unique( $places ) );
		if ( array() === $places ) {
			$this->problems[] = sprintf( 'Focus for %s lists no usable places.', $where );
			return;
		}

		$this->focus[ $language_id ] = $places;
	}
}
