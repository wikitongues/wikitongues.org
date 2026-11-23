<?php
/**
 * Editorial — a release's region overviews and front matter.
 *
 * The country reports carry most of the text; two more Docs feed the hub and
 * the region pages (docs/language-rights.md, Region Doc and Front-matter Doc):
 *
 *   regions[].overview   headline, introduction, findings, evidence cards and
 *                        the region's letter from the director
 *   front_matter         the deck, methodology, the five indicators' notes,
 *                        the answer key, the edition's letter, and the global
 *                        synthesis's planned date
 *
 * Both are optional, so a release without them still imports. Like the
 * reports, they are written as absolute state: a release without a region's
 * overview removes it from the site. Pure, so it is unit-tested; the Importer
 * stores the result.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Normalizes and plans the editorial parts of a release.
 *
 * @phpstan-type Link array{url: string, label: string}
 * @phpstan-type Letter array{html: string, name: string, role: string}
 * @phpstan-type Overview array{headline: string, html: string, sources: list<Link>, findings: list<array{title: string, html: string, sources: list<Link>}>, cards: list<array{report_id: string, country: string, title: string, html: string, links: list<Link>}>, letter: ?Letter}
 * @phpstan-type FrontMatter array{summary: string, methodology: array{html: string, sections: list<array{title: string, html: string}>}, indicators: array<string, array{html: string, example: string}>, answer_key: array{html: string, answers: array<string, string>}, letter: ?Letter, synthesis: array{release_date: string}}
 */
final class Editorial {

	/**
	 * A region's overview from a release, or null when it has none.
	 *
	 * @param array<string, mixed> $region A region from the release file.
	 * @return Overview|null
	 */
	public static function overview( array $region ): ?array {
		$raw = $region['overview'] ?? null;
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$out = array(
			'headline' => self::text( $raw['headline'] ?? '' ),
			'html'     => self::text( $raw['html'] ?? '' ),
			'sources'  => self::links( $raw['sources'] ?? array() ),
			'findings' => array(),
			'cards'    => array(),
			'letter'   => self::letter( $raw['letter'] ?? null ),
		);
		foreach ( self::rows( $raw['findings'] ?? array() ) as $finding ) {
			$out['findings'][] = array(
				'title'   => self::text( $finding['title'] ?? '' ),
				'html'    => self::text( $finding['html'] ?? '' ),
				'sources' => self::links( $finding['sources'] ?? array() ),
			);
		}
		foreach ( self::rows( $raw['cards'] ?? array() ) as $card ) {
			$out['cards'][] = array(
				'report_id' => self::text( $card['report_id'] ?? '' ),
				'country'   => self::text( $card['country'] ?? '' ),
				'title'     => self::text( $card['title'] ?? '' ),
				'html'      => self::text( $card['html'] ?? '' ),
				'links'     => self::links( $card['links'] ?? array() ),
			);
		}
		if ( '' === $out['headline'] && '' === $out['html'] && ! $out['findings'] && ! $out['cards'] && null === $out['letter'] ) {
			return null;
		}
		return $out;
	}

	/**
	 * The release's front matter, or null when it has none.
	 *
	 * @param array<string, mixed> $release A release file.
	 * @return FrontMatter|null
	 */
	public static function front_matter( array $release ): ?array {
		$raw = $release['front_matter'] ?? null;
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$methodology = is_array( $raw['methodology'] ?? null ) ? $raw['methodology'] : array();
		$answer_key  = is_array( $raw['answer_key'] ?? null ) ? $raw['answer_key'] : array();
		$out         = array(
			'summary'     => self::text( $raw['summary'] ?? '' ),
			'methodology' => array(
				'html'     => self::text( $methodology['html'] ?? '' ),
				'sections' => array(),
			),
			'indicators'  => array(),
			'answer_key'  => array(
				'html'    => self::text( $answer_key['html'] ?? '' ),
				'answers' => array(),
			),
			'letter'      => self::letter( $raw['letter'] ?? null ),
			'synthesis'   => array(
				'release_date' => self::date( $raw['synthesis']['release_date'] ?? '' ),
			),
		);
		foreach ( self::rows( $methodology['sections'] ?? array() ) as $section ) {
			$out['methodology']['sections'][] = array(
				'title' => self::text( $section['title'] ?? '' ),
				'html'  => self::text( $section['html'] ?? '' ),
			);
		}
		foreach ( (array) ( $raw['indicators'] ?? array() ) as $key => $indicator ) {
			if ( is_array( $indicator ) ) {
				$out['indicators'][ (string) $key ] = array(
					'html'    => self::text( $indicator['html'] ?? '' ),
					'example' => self::text( $indicator['example'] ?? '' ),
				);
			}
		}
		foreach ( (array) ( $answer_key['answers'] ?? array() ) as $answer => $meaning ) {
			$out['answer_key']['answers'][ Answers::normalize( (string) $answer ) ] = self::text( $meaning );
		}
		return $out;
	}

	/**
	 * A stable fingerprint, so a second import can tell nothing changed.
	 *
	 * @param array<string, mixed>|null $value An overview or the front matter.
	 */
	public static function hash( ?array $value ): string {
		return null === $value ? '' : sha1( (string) wp_json_encode( $value ) );
	}

	/**
	 * What an import would do to the editorial parts, as lines for the dry run.
	 *
	 * @param array<string, mixed>  $release        A release file.
	 * @param array<string, string> $current        Region key → hash of its stored overview ('' for none).
	 * @param string                $current_front  Hash of the stored front matter ('' for none).
	 * @return list<string> Empty when nothing would change.
	 */
	public static function plan( array $release, array $current, string $current_front ): array {
		$lines = array();
		foreach ( (array) $release['regions'] as $region ) {
			$key   = (string) $region['key'];
			$verb  = self::verb( $current[ $key ] ?? '', self::hash( self::overview( $region ) ) );
			$lines = array_merge( $lines, $verb ? array( 'Region overview, ' . $region['name'] . ': ' . $verb ) : array() );
		}
		$verb = self::verb( $current_front, self::hash( self::front_matter( $release ) ) );
		if ( $verb ) {
			$lines[] = 'Front matter: ' . $verb;
		}
		return $lines;
	}

	/**
	 * Create, update, remove or nothing, from two hashes.
	 *
	 * @param string $was Stored hash, '' for none.
	 * @param string $now Release hash, '' for none.
	 */
	private static function verb( string $was, string $now ): string {
		if ( $was === $now ) {
			return '';
		}
		if ( '' === $was ) {
			return 'create';
		}
		return '' === $now ? 'remove' : 'update';
	}

	/**
	 * A letter from the director, or null.
	 *
	 * @param mixed $raw From the release.
	 * @return Letter|null
	 */
	private static function letter( $raw ): ?array {
		if ( ! is_array( $raw ) || '' === self::text( $raw['html'] ?? '' ) ) {
			return null;
		}
		return array(
			'html' => self::text( $raw['html'] ),
			'name' => self::text( $raw['name'] ?? '' ),
			'role' => self::text( $raw['role'] ?? '' ),
		);
	}

	/**
	 * Links as url and label; entries without a URL are dropped.
	 *
	 * @param mixed $raw From the release.
	 * @return list<Link>
	 */
	private static function links( $raw ): array {
		$out = array();
		foreach ( self::rows( $raw ) as $link ) {
			$url = self::text( $link['url'] ?? '' );
			if ( '' !== $url ) {
				$out[] = array(
					'url'   => $url,
					'label' => self::text( $link['label'] ?? '' ),
				);
			}
		}
		return $out;
	}

	/**
	 * The array entries of a list.
	 *
	 * @param mixed $raw From the release.
	 * @return list<array<string, mixed>>
	 */
	private static function rows( $raw ): array {
		return is_array( $raw ) ? array_values( array_filter( $raw, 'is_array' ) ) : array();
	}

	/**
	 * A trimmed string.
	 *
	 * @param mixed $raw From the release.
	 */
	private static function text( $raw ): string {
		return is_scalar( $raw ) ? trim( (string) $raw ) : '';
	}

	/**
	 * A YYYY-MM-DD date, or ''.
	 *
	 * @param mixed $raw From the release.
	 */
	private static function date( $raw ): string {
		$value = self::text( $raw );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}
}
