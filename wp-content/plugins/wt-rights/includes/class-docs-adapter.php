<?php
/**
 * Docs_Adapter — Google Docs API JSON to plain paragraphs.
 *
 * Reads a `documents.get` response fetched with `includeTabsContent=true` and
 * `suggestionsViewMode=PREVIEW_WITHOUT_SUGGESTIONS`, so open suggestions are
 * already rejected. Everything downstream works on the paragraph lists this
 * returns, never on the API's shape, so the parser can be tested with small
 * hand-written fixtures.
 *
 * A paragraph is:
 *   style  The named style: TITLE, HEADING_1 … HEADING_6, NORMAL_TEXT.
 *   text   Its text, trimmed, with soft line breaks as "\n".
 *   runs   Its pieces: text, bold, italic and the link URL ('' for none).
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Flattens a Docs API document into tabs of paragraphs.
 *
 * @phpstan-type Run array{text: string, bold: bool, italic: bool, url: string}
 * @phpstan-type Paragraph array{style: string, text: string, runs: list<Run>}
 * @phpstan-type Tab array{title: string, paragraphs: list<Paragraph>}
 */
final class Docs_Adapter {

	/**
	 * Every tab in order, child tabs after their parent. A document fetched
	 * without tabs becomes one untitled tab.
	 *
	 * @param array<string, mixed> $document A `documents.get` response.
	 * @return list<Tab>
	 */
	public static function tabs( array $document ): array {
		if ( empty( $document['tabs'] ) || ! is_array( $document['tabs'] ) ) {
			return array(
				array(
					'title'      => '',
					'paragraphs' => self::paragraphs( $document['body']['content'] ?? array() ),
				),
			);
		}
		$tabs = array();
		self::collect_tabs( $document['tabs'], $tabs );
		return $tabs;
	}

	/**
	 * Adds tabs and their child tabs to $out, depth first.
	 *
	 * @param array<int, mixed> $tabs API tabs.
	 * @param list<Tab>         $out  Collected tabs.
	 */
	private static function collect_tabs( array $tabs, array &$out ): void {
		foreach ( $tabs as $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}
			$out[] = array(
				'title'      => trim( (string) ( $tab['tabProperties']['title'] ?? '' ) ),
				'paragraphs' => self::paragraphs( $tab['documentTab']['body']['content'] ?? array() ),
			);
			if ( ! empty( $tab['childTabs'] ) && is_array( $tab['childTabs'] ) ) {
				self::collect_tabs( $tab['childTabs'], $out );
			}
		}
	}

	/**
	 * The non-empty paragraphs of a body, including those inside tables.
	 *
	 * @param mixed $content A body's `content` array.
	 * @return list<Paragraph>
	 */
	public static function paragraphs( $content ): array {
		$out = array();
		if ( ! is_array( $content ) ) {
			return $out;
		}
		foreach ( $content as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( isset( $element['paragraph'] ) && is_array( $element['paragraph'] ) ) {
				$paragraph = self::paragraph( $element['paragraph'] );
				if ( '' !== $paragraph['text'] ) {
					$out[] = $paragraph;
				}
				continue;
			}
			foreach ( $element['table']['tableRows'] ?? array() as $row ) {
				foreach ( $row['tableCells'] ?? array() as $cell ) {
					array_push( $out, ...self::paragraphs( $cell['content'] ?? array() ) );
				}
			}
		}
		return $out;
	}

	/**
	 * One API paragraph as a plain paragraph.
	 *
	 * @param array<string, mixed> $paragraph API paragraph.
	 * @return Paragraph
	 */
	private static function paragraph( array $paragraph ): array {
		$runs = array();
		foreach ( $paragraph['elements'] ?? array() as $element ) {
			$run = self::run( is_array( $element ) ? $element : array() );
			if ( null === $run ) {
				continue;
			}
			$last = count( $runs ) - 1;
			// Join neighbours with the same style, which the API often splits.
			if ( $last >= 0 && $runs[ $last ]['bold'] === $run['bold'] && $runs[ $last ]['italic'] === $run['italic'] && $runs[ $last ]['url'] === $run['url'] ) {
				$runs[ $last ]['text'] .= $run['text'];
				continue;
			}
			$runs[] = $run;
		}
		if ( $runs ) {
			$runs[0]['text']                    = ltrim( $runs[0]['text'] );
			$runs[ count( $runs ) - 1 ]['text'] = rtrim( $runs[ count( $runs ) - 1 ]['text'] );
		}
		$runs = array_values( array_filter( $runs, static fn( array $r ): bool => '' !== $r['text'] ) );

		return array(
			'style' => (string) ( $paragraph['paragraphStyle']['namedStyleType'] ?? 'NORMAL_TEXT' ),
			'text'  => trim( implode( '', array_column( $runs, 'text' ) ) ),
			'runs'  => $runs,
		);
	}

	/**
	 * One paragraph element as a run, or null for elements with no text.
	 *
	 * @param array<string, mixed> $element API paragraph element.
	 * @return Run|null
	 */
	private static function run( array $element ): ?array {
		if ( isset( $element['textRun'] ) ) {
			$style = $element['textRun']['textStyle'] ?? array();
			$text  = (string) ( $element['textRun']['content'] ?? '' );
			// Drop the paragraph's own newline first; a soft line break (\v) inside it stays, as "\n".
			$text = str_replace( array( "\n", "\r", "\u{000B}" ), array( '', '', "\n" ), $text );
			return array(
				'text'   => $text,
				'bold'   => ! empty( $style['bold'] ),
				'italic' => ! empty( $style['italic'] ),
				'url'    => (string) ( $style['link']['url'] ?? '' ),
			);
		}
		if ( isset( $element['richLink'] ) ) {
			$props = $element['richLink']['richLinkProperties'] ?? array();
			$url   = (string) ( $props['uri'] ?? '' );
			return array(
				'text'   => (string) ( $props['title'] ?? $url ),
				'bold'   => false,
				'italic' => false,
				'url'    => $url,
			);
		}
		return null;
	}
}
