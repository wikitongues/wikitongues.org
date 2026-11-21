<?php
/**
 * Doc_Parser — a country report Doc as structured content.
 *
 * Input is the tabs from Docs_Adapter; the shape it expects comes from
 * wt_rights_structure(). It never stops at the first problem: it reads as much
 * as it can and lists every problem, so one pass tells the editor everything
 * to fix. Errors block a release; warnings are reported and don't.
 *
 * Output:
 *   country     The name after the title prefix.
 *   sections    Prose sections by key: html and sources.
 *   dimensions  Indicator key → answer, html, sources.
 *   rating      ante, trend, headline (the bold sentence), html, sources.
 *   story_card  From the WordPress tab: title, html, links; null when absent.
 *   problems    code, severity, message.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Parses one country Doc.
 *
 * @phpstan-import-type Paragraph from Docs_Adapter
 * @phpstan-import-type Tab from Docs_Adapter
 * @phpstan-type Source array{url: string, label: string}
 * @phpstan-type Problem array{code: string, severity: string, message: string}
 */
final class Doc_Parser {

	/**
	 * The structure, from wt_rights_structure().
	 *
	 * @var array<string, mixed>
	 */
	private array $structure;

	/**
	 * Problems found so far.
	 *
	 * @var list<Problem>
	 */
	private array $problems = array();

	/**
	 * Builds a parser for one structure.
	 *
	 * @param array<string, mixed> $structure From wt_rights_structure().
	 */
	public function __construct( array $structure ) {
		$this->structure = $structure;
	}

	/**
	 * Parses a Doc's tabs.
	 *
	 * @param list<Tab> $tabs From Docs_Adapter::tabs().
	 * @return array<string, mixed>
	 */
	public function parse( array $tabs ): array {
		$this->problems = array();

		$wordpress = null;
		$main      = null;
		foreach ( $tabs as $tab ) {
			if ( 0 === strcasecmp( $tab['title'], (string) $this->structure['wordpress_tab'] ) ) {
				$wordpress = $wordpress ?? $tab;
			} else {
				$main = $main ?? $tab;
			}
		}

		$report = array(
			'country'    => '',
			'sections'   => array(),
			'dimensions' => array(),
			'rating'     => null,
			'story_card' => null !== $wordpress ? $this->story_card( $wordpress['paragraphs'] ) : null,
		);

		if ( null === $main ) {
			$this->problem( 'empty_doc', 'The Doc has no report tab.' );
		} else {
			$report = array_merge( $report, $this->main( $main['paragraphs'] ) );
		}

		$report['problems'] = $this->problems;
		return $report;
	}

	/**
	 * The report tab: title, then sections.
	 *
	 * @param list<Paragraph> $paragraphs The tab's paragraphs.
	 * @return array<string, mixed>
	 */
	private function main( array $paragraphs ): array {
		$out = array(
			'country'    => '',
			'sections'   => array(),
			'dimensions' => array(),
			'rating'     => null,
		);

		$title = null;
		foreach ( $paragraphs as $paragraph ) {
			if ( 'TITLE' === $paragraph['style'] ) {
				$title = $paragraph['text'];
				break;
			}
		}
		$prefix = (string) $this->structure['title_prefix'];
		if ( null === $title ) {
			$this->problem( 'missing_title', 'The Doc has no Title line ("' . $prefix . '…").' );
		} elseif ( 0 === stripos( $title, $prefix ) ) {
			$out['country'] = trim( substr( $title, strlen( $prefix ) ) );
		} else {
			$out['country'] = $title;
			$this->problem( 'title_wording', 'The title should begin "' . $prefix . '"; it reads "' . $title . '".', 'warning' );
		}

		$blocks = $this->split_sections( $paragraphs );
		$found  = array_keys( $blocks );
		$order  = array();

		foreach ( $this->structure['sections'] as $section ) {
			$key = $section['key'];
			if ( ! array_key_exists( $key, $blocks ) ) {
				$this->problem( 'missing_section', 'Missing section "' . $section['heading'] . '" (Heading 1).' );
				continue;
			}
			$order[] = $key;
			$body    = $blocks[ $key ];
			switch ( $section['type'] ) {
				case 'dimensions':
					$out['dimensions'] = $this->dimensions( $body );
					break;
				case 'rating':
					$out['rating'] = $this->rating( $body );
					break;
				default:
					if ( ! $body && empty( $section['optional'] ) ) {
						$this->problem( 'empty_section', 'Section "' . $section['heading'] . '" is empty.' );
					}
					$out['sections'][ $key ] = $this->prose( $body );
			}
		}

		$found_known = array_values( array_intersect( $found, $order ) );
		if ( $found_known !== $order ) {
			$this->problem( 'section_order', 'Sections are out of order.', 'warning' );
		}
		return $out;
	}

	/**
	 * Groups paragraphs under their Heading 1, keyed by section key.
	 * Text before the first heading is the title area and is dropped.
	 *
	 * @param list<Paragraph> $paragraphs The tab's paragraphs.
	 * @return array<string, list<Paragraph>>
	 */
	private function split_sections( array $paragraphs ): array {
		$blocks  = array();
		$current = null;
		foreach ( $paragraphs as $paragraph ) {
			if ( 'HEADING_1' === $paragraph['style'] ) {
				$key = $this->section_key( $paragraph['text'] );
				if ( null === $key ) {
					$this->problem( 'unknown_section', 'Unknown Heading 1 "' . $paragraph['text'] . '".' );
					$current = null;
					continue;
				}
				if ( isset( $blocks[ $key ] ) ) {
					$this->problem( 'duplicate_section', 'Section "' . $paragraph['text'] . '" appears twice.' );
				}
				$blocks[ $key ] = array();
				$current        = $key;
				continue;
			}
			if ( null !== $current ) {
				$blocks[ $current ][] = $paragraph;
			}
		}
		return $blocks;
	}

	/**
	 * The section key for a heading, or null when it matches none.
	 *
	 * @param string $heading Heading text.
	 */
	private function section_key( string $heading ): ?string {
		$wanted = self::fold( $heading );
		foreach ( $this->structure['sections'] as $section ) {
			$names = array_merge( array( $section['heading'] ), $section['aliases'] ?? array() );
			foreach ( $names as $name ) {
				if ( self::fold( $name ) === $wanted ) {
					return $section['key'];
				}
			}
		}
		return null;
	}

	/**
	 * Language rights dimensions: each question, its answer line, text and sources.
	 *
	 * @param list<Paragraph> $paragraphs The section's paragraphs.
	 * @return array<string, array{answer: string, html: string, sources: list<Source>}>
	 */
	private function dimensions( array $paragraphs ): array {
		$by_question = array();
		foreach ( $this->structure['indicators'] as $indicator ) {
			foreach ( array_merge( array( $indicator['question'] ), $indicator['aliases'] ?? array() ) as $question ) {
				$by_question[ self::fold( $question ) ] = $indicator['key'];
			}
		}

		$blocks  = array();
		$current = null;
		foreach ( $paragraphs as $paragraph ) {
			$key = $by_question[ self::fold( $paragraph['text'] ) ] ?? null;
			if ( null !== $key ) {
				if ( isset( $blocks[ $key ] ) ) {
					$this->problem( 'duplicate_question', 'A question appears twice: "' . $paragraph['text'] . '".' );
				}
				$blocks[ $key ] = array();
				$current        = $key;
				continue;
			}
			if ( null === $current ) {
				$this->problem( 'stray_text', 'Text before the first question in Language rights dimensions: "' . self::excerpt( $paragraph['text'] ) . '".', 'warning' );
				continue;
			}
			$blocks[ $current ][] = $paragraph;
		}

		$out = array();
		foreach ( $this->structure['indicators'] as $indicator ) {
			$key = $indicator['key'];
			if ( ! isset( $blocks[ $key ] ) ) {
				$this->problem( 'missing_question', 'Missing question: "' . $indicator['question'] . '".' );
				continue;
			}
			$answer = null;
			$rest   = array();
			foreach ( $blocks[ $key ] as $paragraph ) {
				$line = null === $answer ? Answers::from_line( $paragraph['text'] ) : null;
				if ( null !== $line ) {
					$answer = $line;
					continue;
				}
				$rest[] = $paragraph;
			}
			$answer      = $this->check_answer( $answer, $indicator['answers'], $indicator['label'] );
			$out[ $key ] = array( 'answer' => $answer ) + $this->prose( $rest );
		}
		return $out;
	}

	/**
	 * Overall rating: the two answer lines, the bold headline, text and sources.
	 *
	 * @param list<Paragraph> $paragraphs The section's paragraphs.
	 * @return array{ante: string, trend: string, headline: string, html: string, sources: list<Source>}
	 */
	private function rating( array $paragraphs ): array {
		$answers  = array();
		$headline = '';
		$rest     = array();
		$pending  = null; // A rating whose question line came without its answer.
		foreach ( $paragraphs as $paragraph ) {
			$key  = $this->rating_key( $paragraph['text'], $answers );
			$line = Answers::from_line( $paragraph['text'] );
			if ( null !== $line && ( null !== $key || null !== $pending ) ) {
				$answers[ $key ?? $pending ] = $line;
				$pending                     = null;
				continue;
			}
			if ( null !== $key && null === $line && str_ends_with( rtrim( $paragraph['text'] ), '?' ) ) {
				$pending = $key; // "Was the status quo ante …?" with the answer on the next line.
				continue;
			}
			if ( '' === $headline && self::all_bold( $paragraph ) ) {
				$headline = $paragraph['text'];
				continue;
			}
			$rest[] = $paragraph;
		}

		$out = array();
		foreach ( $this->structure['ratings'] as $rating ) {
			$out[ $rating['key'] ] = $this->check_answer( $answers[ $rating['key'] ] ?? null, $rating['answers'], $rating['label'] );
		}
		if ( '' === $headline ) {
			$this->problem( 'missing_headline', 'Overall Rating has no bold headline sentence.' );
		}
		$out['headline'] = $headline;
		return $out + $this->prose( $rest );
	}

	/**
	 * The rating a line asks about, by its phrase, unless already answered.
	 *
	 * @param string                $text    A paragraph's text.
	 * @param array<string, string> $answers Ratings answered so far.
	 */
	private function rating_key( string $text, array $answers ): ?string {
		foreach ( $this->structure['ratings'] as $rating ) {
			if ( ! isset( $answers[ $rating['key'] ] ) && false !== stripos( $text, $rating['phrase'] ) ) {
				return $rating['key'];
			}
		}
		return null;
	}

	/**
	 * The story card from the WordPress tab, or null when it has none.
	 *
	 * @param list<Paragraph> $paragraphs The tab's paragraphs.
	 * @return array{title: string, html: string, links: list<Source>}|null
	 */
	private function story_card( array $paragraphs ): ?array {
		$title = null;
		$body  = array();
		foreach ( $paragraphs as $paragraph ) {
			if ( null === $title ) {
				if ( 'HEADING_2' === $paragraph['style'] ) {
					$title = $paragraph['text'];
				}
				continue;
			}
			if ( str_starts_with( $paragraph['style'], 'HEADING_' ) ) {
				break; // One card per tab.
			}
			$body[] = $paragraph;
		}
		if ( null === $title ) {
			return null;
		}
		$prose = $this->prose( $body );
		$words = str_word_count( self::plain_text( $prose['html'] ) );
		$max   = (int) $this->structure['story_card_max_words'];
		if ( $words > $max ) {
			$this->problem( 'story_card_long', 'The story card is ' . $words . ' words; the limit is ' . $max . '.', 'warning' );
		}
		return array(
			'title' => $title,
			'html'  => $prose['html'],
			'links' => $prose['sources'],
		);
	}

	/**
	 * Paragraphs as HTML, with source paragraphs pulled out as a list.
	 *
	 * @param list<Paragraph> $paragraphs Paragraphs.
	 * @return array{html: string, sources: list<Source>}
	 */
	private function prose( array $paragraphs ): array {
		$html    = '';
		$sources = array();
		foreach ( $paragraphs as $paragraph ) {
			$found = self::sources( $paragraph );
			if ( null !== $found ) {
				array_push( $sources, ...$found );
				continue;
			}
			$html .= '<p>' . self::runs_html( $paragraph['runs'] ) . "</p>\n";
		}
		return array(
			'html'    => $html,
			'sources' => self::unique_sources( $sources ),
		);
	}

	/**
	 * A paragraph's sources when it is a source paragraph (only links, bare
	 * URLs and separators), or null when it is prose.
	 *
	 * @param Paragraph $paragraph A paragraph.
	 * @return list<Source>|null
	 */
	public static function sources( array $paragraph ): ?array {
		$sources = array();
		foreach ( $paragraph['runs'] as $run ) {
			if ( '' !== $run['url'] && self::is_http( $run['url'] ) ) {
				$text      = trim( $run['text'] );
				$sources[] = array(
					'url'   => $run['url'],
					'label' => self::looks_like_url( $text ) ? '' : $text,
				);
				continue;
			}
			preg_match_all( '#https?://[^\s<>"]+#i', $run['text'], $m );
			foreach ( $m[0] as $url ) {
				$sources[] = array(
					'url'   => rtrim( $url, '.,;)' ),
					'label' => '',
				);
			}
			$leftover = (string) preg_replace( '#https?://[^\s<>"]+#i', '', $run['text'] );
			if ( '' !== trim( $leftover, " \n\t·•|,;–—-" ) ) {
				return null;
			}
		}
		return $sources ? $sources : null;
	}

	/**
	 * Runs as inline HTML: escaped text, line breaks, bold, italic, http(s) links.
	 *
	 * @param list<array{text: string, bold: bool, italic: bool, url: string}> $runs Runs.
	 */
	public static function runs_html( array $runs ): string {
		$html = '';
		foreach ( $runs as $run ) {
			$piece = nl2br( htmlspecialchars( $run['text'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ), false );
			if ( $run['italic'] ) {
				$piece = '<em>' . $piece . '</em>';
			}
			if ( $run['bold'] ) {
				$piece = '<strong>' . $piece . '</strong>';
			}
			if ( '' !== $run['url'] && self::is_http( $run['url'] ) ) {
				$piece = '<a href="' . htmlspecialchars( $run['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . '">' . $piece . '</a>';
			}
			$html .= $piece;
		}
		return str_replace( "\n", '', $html );
	}

	/**
	 * Checks an answer against the allowed set.
	 *
	 * @param string|null  $answer  The normalized answer, or null when no line was found.
	 * @param list<string> $allowed Allowed answers.
	 * @param string       $label   What is being answered, for the message.
	 * @return string The answer, or '' when missing or not allowed.
	 */
	private function check_answer( ?string $answer, array $allowed, string $label ): string {
		if ( null === $answer || '' === $answer ) {
			$this->problem( 'missing_answer', $label . ': no "Your answer:" line.' );
			return '';
		}
		if ( ! in_array( $answer, $allowed, true ) ) {
			$this->problem( 'invalid_answer', $label . ': "' . $answer . '" is not one of ' . implode( ', ', $allowed ) . '.' );
			return '';
		}
		return $answer;
	}

	/**
	 * Records a problem.
	 *
	 * @param string $code     Stable code, for tests and tooling.
	 * @param string $message  For the editor.
	 * @param string $severity `error` blocks a release; `warning` doesn't.
	 */
	private function problem( string $code, string $message, string $severity = 'error' ): void {
		$this->problems[] = array(
			'code'     => $code,
			'severity' => $severity,
			'message'  => $message,
		);
	}

	/**
	 * Whether every visible run in a paragraph is bold.
	 *
	 * @param Paragraph $paragraph A paragraph.
	 */
	private static function all_bold( array $paragraph ): bool {
		$seen = false;
		foreach ( $paragraph['runs'] as $run ) {
			if ( '' === trim( $run['text'] ) ) {
				continue;
			}
			if ( ! $run['bold'] ) {
				return false;
			}
			$seen = true;
		}
		return $seen;
	}

	/**
	 * Text folded for matching: lower case, single spaces, curly quotes straightened.
	 *
	 * @param string $text Text.
	 */
	public static function fold( string $text ): string {
		$text = str_replace( array( '’', '‘', '“', '”', "\u{00A0}" ), array( "'", "'", '"', '"', ' ' ), $text );
		return strtolower( trim( (string) preg_replace( '/\s+/u', ' ', $text ) ) );
	}

	/**
	 * Drops repeated URLs, keeping the first.
	 *
	 * @param list<Source> $sources Sources.
	 * @return list<Source>
	 */
	private static function unique_sources( array $sources ): array {
		$seen = array();
		$out  = array();
		foreach ( $sources as $source ) {
			if ( isset( $seen[ $source['url'] ] ) ) {
				continue;
			}
			$seen[ $source['url'] ] = true;
			$out[]                  = $source;
		}
		return $out;
	}

	/**
	 * Whether a URL is http or https.
	 *
	 * @param string $url URL.
	 */
	private static function is_http( string $url ): bool {
		return (bool) preg_match( '#^https?://#i', $url );
	}

	/**
	 * Whether link text is itself a URL.
	 *
	 * @param string $text Link text.
	 */
	private static function looks_like_url( string $text ): bool {
		return '' === $text || (bool) preg_match( '#^(https?://|www\.)\S+$#i', $text );
	}

	/**
	 * HTML as plain text, for word counts.
	 *
	 * @param string $html HTML.
	 */
	private static function plain_text( string $html ): string {
		return trim( html_entity_decode( strip_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * The first 60 characters of a text.
	 *
	 * @param string $text Text.
	 */
	private static function excerpt( string $text ): string {
		return mb_strlen( $text ) > 60 ? mb_substr( $text, 0, 60 ) . '…' : $text;
	}
}
