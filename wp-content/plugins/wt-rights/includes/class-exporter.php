<?php
/**
 * Exporter — Airtable rows and their Docs into one release.
 *
 * Pure apart from the two callables it is given: one fetches a Doc, one makes
 * a slug. The CLI wires in the real clients; tests pass canned ones.
 *
 * A release is cumulative: it holds every region of the previous release plus
 * the regions asked for now, so any one file rebuilds the whole report.
 *
 * Per country, by what its Research Status means (see structure.php):
 *   publish      Doc fetched, parsed, checked against Airtable, versioned.
 *   held         Listed as forthcoming; not fetched.
 *   not_started  Listed as forthcoming; not fetched.
 *   blocking     Blocks its region. With preview, exported as a draft instead.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Builds a release.
 *
 * @phpstan-import-type Problem from Doc_Parser
 * @phpstan-type Row array{record_id: string, name: string, status: string, region: string, report_link: string, nation_id: string, answers: array<string, string>}
 */
final class Exporter {

	public const SCHEMA = 1;

	/**
	 * The structure, from wt_rights_structure().
	 *
	 * @var array<string, mixed>
	 */
	private array $structure;

	/**
	 * Fetches a Doc by ID: string → `documents.get` response.
	 *
	 * @var callable(string): array<string, mixed>
	 */
	private $fetch_doc;

	/**
	 * Makes a slug from a name.
	 *
	 * @var callable(string): string
	 */
	private $slugify;

	/**
	 * Builds an exporter.
	 *
	 * @param array<string, mixed>                  $structure From wt_rights_structure().
	 * @param callable(string): array<string, mixed> $fetch_doc Fetches a Doc by ID.
	 * @param callable(string): string              $slugify   Makes a slug, e.g. sanitize_title.
	 */
	public function __construct( array $structure, callable $fetch_doc, callable $slugify ) {
		$this->structure = $structure;
		$this->fetch_doc = $fetch_doc;
		$this->slugify   = $slugify;
	}

	/**
	 * An Airtable Reports record as a plain row.
	 *
	 * @param array{id: string, fields: array<string, mixed>} $record A record keyed by field ID.
	 * @param array<string, string>                          $fields Field key → field ID (config/airtable.php).
	 * @return Row
	 */
	public static function row( array $record, array $fields ): array {
		$value   = static function ( string $key ) use ( $record, $fields ): string {
			$v = $record['fields'][ $fields[ $key ] ?? '' ] ?? '';
			if ( is_array( $v ) ) {
				$v = $v['name'] ?? ( $v[0] ?? '' ); // A select object, or a list of linked record IDs.
			}
			return is_scalar( $v ) ? trim( (string) $v ) : '';
		};
		$answers = array();
		foreach ( array( 'i1', 'i2', 'i3', 'i4', 'i5', 'ante', 'trend' ) as $key ) {
			$answers[ $key ] = Answers::normalize( $value( $key ) );
		}
		return array(
			'record_id'   => $record['id'],
			'name'        => $value( 'name' ),
			'status'      => $value( 'status' ),
			'region'      => $value( 'region' ),
			'report_link' => $value( 'report_link' ),
			'nation_id'   => $value( 'nation' ),
			'answers'     => $answers,
		);
	}

	/**
	 * Region key → release date (Y-m-d), from the Publishing Docs table.
	 *
	 * @param list<array{id: string, fields: array<string, mixed>}> $records Publishing Docs records, keyed by field ID.
	 * @param array<string, string>                                $fields  Field key → field ID.
	 * @return array<string, string>
	 */
	public function release_dates( array $records, array $fields ): array {
		$region_of = array();
		foreach ( $this->structure['regions'] as $region ) {
			$region_of[ Doc_Parser::fold( $region['airtable'] ) ] = $region['key'];
		}
		$dates = array();
		foreach ( $records as $record ) {
			$region = $region_of[ Doc_Parser::fold( (string) ( $record['fields'][ $fields['region'] ] ?? '' ) ) ] ?? '';
			$date   = (string) ( $record['fields'][ $fields['release_date'] ] ?? '' );
			if ( '' !== $region && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				$dates[ $region ] = $date;
			}
		}
		return $dates;
	}

	/**
	 * Reports record ID → Changes notes dated on or after the previous release
	 * was built (all notes when there is none), oldest first.
	 *
	 * @param list<array{id: string, fields: array<string, mixed>}> $records  Changes records, keyed by field ID.
	 * @param array<string, string>                                $fields   Field key → field ID.
	 * @param array<string, mixed>|null                            $previous The previous release.
	 * @return array<string, list<string>>
	 */
	public static function notes_since( array $records, array $fields, ?array $previous ): array {
		$since = substr( (string) ( $previous['built_at'] ?? '' ), 0, 10 );
		usort( $records, static fn( array $a, array $b ): int => strcmp( (string) ( $a['fields'][ $fields['date'] ] ?? '' ), (string) ( $b['fields'][ $fields['date'] ] ?? '' ) ) );
		$notes = array();
		foreach ( $records as $record ) {
			$date = (string) ( $record['fields'][ $fields['date'] ] ?? '' );
			$note = trim( (string) ( $record['fields'][ $fields['note'] ] ?? '' ) );
			if ( '' === $note || ( '' !== $since && $date < $since ) ) {
				continue;
			}
			foreach ( (array) ( $record['fields'][ $fields['report'] ] ?? array() ) as $report_id ) {
				$notes[ (string) $report_id ][] = $note;
			}
		}
		return $notes;
	}

	/**
	 * Builds a release.
	 *
	 * Options:
	 *   release        The release number, e.g. "2027.1".
	 *   regions        Region keys to add to the previous release's regions.
	 *   preview        Export unfinished countries as drafts; their problems become warnings.
	 *   today          Y-m-d, for changelog dates and publication.
	 *   release_dates  Region key → Y-m-d. A report is published once its date has passed.
	 *   notes          Record ID → Changes notes dated since the previous release.
	 *
	 * @param list<Row>                 $rows     Every Reports row.
	 * @param array<string, mixed>|null $previous The previous release, or null.
	 * @param array<string, mixed>      $options  See above.
	 * @return array{release: array<string, mixed>, countries: list<array{name: string, region: string, status: string, outcome: string, problems: list<Problem>}>, errors: int}
	 */
	public function build( array $rows, ?array $previous, array $options ): array {
		$validator = new Validator( $this->structure );
		$preview   = ! empty( $options['preview'] );
		$today     = (string) ( $options['today'] ?? gmdate( 'Y-m-d' ) );
		$regions   = $this->regions_in_release( $previous, (array) ( $options['regions'] ?? array() ) );
		$region_of = array();
		foreach ( $this->structure['regions'] as $region ) {
			$region_of[ Doc_Parser::fold( $region['airtable'] ) ] = $region['key'];
		}

		$previous_reports = array();
		foreach ( (array) ( $previous['reports'] ?? array() ) as $entry ) {
			$previous_reports[ $entry['record_id'] ] = $entry;
		}

		$countries   = array();
		$reports     = array();
		$forthcoming = array();
		$slugs       = array_fill_keys( array_column( $this->structure['regions'], 'key' ), 'region' );

		foreach ( $rows as $row ) {
			$region = $region_of[ Doc_Parser::fold( $row['region'] ) ] ?? '';
			if ( '' === $region ) {
				$countries[] = $this->country( $row, '', 'skipped', array( self::error( 'unknown_region', 'Publishing Region "' . $row['region'] . '" is not one of the seven.' ) ) );
				continue;
			}
			if ( ! in_array( $region, $regions, true ) ) {
				continue;
			}

			$role     = $validator->status_role( $row['status'] );
			$prior    = $previous_reports[ $row['record_id'] ] ?? null;
			$slug     = (string) ( $prior['slug'] ?? ( $this->slugify )( $row['name'] ) );
			$problems = array();

			if ( isset( $slugs[ $slug ] ) ) {
				$problems[] = self::error( 'slug_taken', 'Its URL slug "' . $slug . '" is already used by ' . $slugs[ $slug ] . '.' );
			}
			$slugs[ $slug ] = $row['name'];

			if ( 'held' === $role || 'not_started' === $role ) {
				$forthcoming[ $region ][] = array(
					'record_id' => $row['record_id'],
					'name'      => $row['name'],
					'slug'      => $slug,
				);
				$countries[]              = $this->country( $row, $region, 'forthcoming', $problems );
				continue;
			}
			if ( 'unknown' === $role ) {
				$problems[]  = self::error( 'unknown_status', 'Unknown Research Status "' . $row['status'] . '".' );
				$countries[] = $this->country( $row, $region, 'skipped', $problems );
				continue;
			}
			if ( 'blocking' === $role && ! $preview ) {
				$problems[]  = self::error( 'region_blocked', '"' . $row['status'] . '" blocks the region: set it to Completed or Held.' );
				$countries[] = $this->country( $row, $region, 'blocking', $problems );
				continue;
			}

			$entry = $this->entry( $row, $region, $slug, $prior, $validator, $options, $today, $problems );
			if ( 'blocking' === $role ) {
				$entry['draft'] = true;
				$problems       = self::as_warnings( $problems );
			}
			$reports[ $region ][] = $entry;
			$countries[]          = $this->country( $row, $region, 'blocking' === $role ? 'draft' : 'exported', $problems );
		}

		$release = array(
			'schema'   => self::SCHEMA,
			'release'  => (string) ( $options['release'] ?? '' ),
			'built_at' => gmdate( 'c' ),
			'preview'  => $preview,
			'regions'  => array(),
			'reports'  => array(),
		);
		foreach ( $this->structure['regions'] as $order => $region ) {
			if ( ! in_array( $region['key'], $regions, true ) ) {
				continue;
			}
			$entries              = $reports[ $region['key'] ] ?? array();
			$release['regions'][] = array(
				'key'         => $region['key'],
				'name'        => $region['name'],
				'order'       => $order + 1,
				'reports'     => array_column( $entries, 'record_id' ),
				'forthcoming' => $forthcoming[ $region['key'] ] ?? array(),
			);
			$release['reports']   = array_merge( $release['reports'], $entries );
		}

		$errors = 0;
		foreach ( $countries as $country ) {
			$errors += count( array_filter( $country['problems'], static fn( array $p ): bool => 'error' === $p['severity'] ) );
		}
		return array(
			'release'   => $release,
			'countries' => $countries,
			'errors'    => $errors,
		);
	}

	/**
	 * One exported report.
	 *
	 * @param Row                       $row       The Airtable row.
	 * @param string                    $region    Region key.
	 * @param string                    $slug      URL slug.
	 * @param array<string, mixed>|null $prior     The report in the previous release.
	 * @param Validator                 $validator The validator.
	 * @param array<string, mixed>      $options   Build options.
	 * @param string                    $today     Y-m-d.
	 * @param list<Problem>             $problems  Problems so far; added to.
	 * @return array<string, mixed>
	 */
	private function entry( array $row, string $region, string $slug, ?array $prior, Validator $validator, array $options, string $today, array &$problems ): array {
		$doc_id  = Google_Docs::document_id( $row['report_link'] );
		$content = null;
		if ( '' === $doc_id ) {
			$problems[] = self::error( 'no_doc', 'The Report link is not a Google Doc: "' . $row['report_link'] . '".' );
		} else {
			try {
				$parsed   = ( new Doc_Parser( $this->structure ) )->parse( Docs_Adapter::tabs( ( $this->fetch_doc )( $doc_id ) ) );
				$problems = array_merge( $problems, $validator->report( $row, $parsed ) );
				$content  = array(
					'sections'   => $parsed['sections'],
					'dimensions' => $parsed['dimensions'],
					'rating'     => $parsed['rating'],
					'story_card' => $parsed['story_card'],
				);
			} catch ( \Throwable $e ) {
				$problems[] = self::error( 'doc_unreadable', 'The Doc could not be read: ' . $e->getMessage() );
			}
		}

		$hash      = Versioner::hash(
			array(
				'name'    => $row['name'],
				'answers' => $row['answers'],
				'content' => $content,
			)
		);
		$date      = (string) ( $options['release_dates'][ $region ] ?? '' );
		$published = '' !== $date && $date <= $today;
		$notes     = array_values( (array) ( $options['notes'][ $row['record_id'] ] ?? array() ) );
		$next      = Versioner::next(
			null === $prior ? null : array(
				'version' => (string) $prior['version'],
				'hash'    => (string) $prior['hash'],
				'answers' => (array) $prior['answers'],
			),
			$hash,
			$row['answers'],
			$published,
			$notes
		);
		$problems  = array_merge( $problems, $next['problems'] );
		$changelog = (array) ( $prior['changelog'] ?? array() );
		if ( $published ) {
			$changelog = Versioner::changelog( $changelog, $next['version'], $today, $next['changed'] ? $notes : array(), $next['changed_answers'] );
		}

		return array(
			'record_id' => $row['record_id'],
			'nation_id' => $row['nation_id'],
			'slug'      => $slug,
			'name'      => $row['name'],
			'region'    => $region,
			'draft'     => false,
			'doc_id'    => $doc_id,
			'version'   => $next['version'],
			'hash'      => $hash,
			'answers'   => $row['answers'],
			'content'   => $content,
			'changelog' => $changelog,
		);
	}

	/**
	 * The previous release's regions plus the ones asked for, as keys in release order.
	 *
	 * @param array<string, mixed>|null $previous The previous release.
	 * @param list<string>              $asked    Region keys.
	 * @return list<string>
	 */
	private function regions_in_release( ?array $previous, array $asked ): array {
		$keys    = array_column( (array) ( $previous['regions'] ?? array() ), 'key' );
		$keys    = array_merge( $keys, $asked );
		$ordered = array();
		foreach ( $this->structure['regions'] as $region ) {
			if ( in_array( $region['key'], $keys, true ) ) {
				$ordered[] = $region['key'];
			}
		}
		return $ordered;
	}

	/**
	 * A country line for the report.
	 *
	 * @param Row           $row      The row.
	 * @param string        $region   Region key, or ''.
	 * @param string        $outcome  exported, draft, forthcoming, blocking or skipped.
	 * @param list<Problem> $problems Problems.
	 * @return array{name: string, region: string, status: string, outcome: string, problems: list<Problem>}
	 */
	private function country( array $row, string $region, string $outcome, array $problems ): array {
		return array(
			'name'     => $row['name'],
			'region'   => $region,
			'status'   => $row['status'],
			'outcome'  => $outcome,
			'problems' => $problems,
		);
	}

	/**
	 * Problems with every error turned into a warning.
	 *
	 * @param list<Problem> $problems Problems.
	 * @return list<Problem>
	 */
	private static function as_warnings( array $problems ): array {
		return array_map(
			static function ( array $p ): array {
				$p['severity'] = 'warning';
				return $p;
			},
			$problems
		);
	}

	/**
	 * An error.
	 *
	 * @param string $code    Stable code.
	 * @param string $message For the editor.
	 * @return Problem
	 */
	private static function error( string $code, string $message ): array {
		return array(
			'code'     => $code,
			'severity' => 'error',
			'message'  => $message,
		);
	}
}
