<?php
/**
 * Builds Google Docs API responses for the parser tests.
 *
 * Every fixture is about "Testland", an invented country, with invented text.
 * Real report text is embargoed until release and must never be committed to
 * this public repository.
 */
final class RightsFixture {

	public const QUESTIONS = array(
		'i1' => 'Does the government prohibit or significantly hinder the use of any language?',
		'i2' => 'Does the government seriously and unfairly repress people because of their language use, linguistic identity, or language advocacy?',
		'i3' => 'Does the country grant official or co-official legal recognition to historically rooted languages beyond those already dominant in state institutions?',
		'i4' => 'Does the country facilitate the transmission and development of historically rooted languages through state funding or public institutions?',
		'i5' => 'Does the country support revitalization initiatives for endangered historically rooted languages?',
	);

	/**
	 * One API paragraph element.
	 *
	 * @param string              $style A named style, e.g. HEADING_1.
	 * @param string|array<mixed> ...$runs Plain strings, or arrays with text, bold, italic, url.
	 */
	public static function p( string $style, ...$runs ): array {
		$elements = array();
		foreach ( $runs as $run ) {
			$run        = is_string( $run ) ? array( 'text' => $run ) : $run;
			$text_style = array();
			if ( ! empty( $run['bold'] ) ) {
				$text_style['bold'] = true;
			}
			if ( ! empty( $run['italic'] ) ) {
				$text_style['italic'] = true;
			}
			if ( ! empty( $run['url'] ) ) {
				$text_style['link'] = array( 'url' => $run['url'] );
			}
			$elements[] = array(
				'textRun' => array(
					'content'   => $run['text'],
					'textStyle' => $text_style,
				),
			);
		}
		// The API ends every paragraph's last run with its newline.
		$last                                     = count( $elements ) - 1;
		$elements[ $last ]['textRun']['content'] .= "\n";
		return array(
			'paragraph' => array(
				'elements'       => $elements,
				'paragraphStyle' => array( 'namedStyleType' => $style ),
			),
		);
	}

	/** A normal paragraph. */
	public static function text( string $text ): array {
		return self::p( 'NORMAL_TEXT', $text );
	}

	/** A source paragraph: links separated by soft line breaks, as researchers paste them. */
	public static function sources( string ...$urls ): array {
		$runs = array();
		foreach ( $urls as $i => $url ) {
			if ( $i > 0 ) {
				$runs[] = "\u{000B}";
			}
			$runs[] = array(
				'text' => $url,
				'url'  => $url,
			);
		}
		return self::p( 'NORMAL_TEXT', ...$runs );
	}

	/**
	 * The report tab's elements for a complete, valid Testland Doc.
	 *
	 * Options: answers (key → typed answer), ante, trend, headline,
	 * questions (key → wording), skip (section headings to leave out),
	 * testimonies (false for an empty section).
	 *
	 * @param array<string, mixed> $o Options.
	 * @return list<array<mixed>>
	 */
	public static function report( array $o = array() ): array {
		$answers   = ( $o['answers'] ?? array() ) + array(
			'i1' => 'Yes',
			'i2' => 'Unclear',
			'i3' => 'No',
			'i4' => 'Yes',
			'i5' => 'Not applicable',
		);
		$questions = ( $o['questions'] ?? array() ) + self::QUESTIONS;
		$skip      = $o['skip'] ?? array();
		$out       = array( self::p( 'TITLE', 'The State of Language Rights in Testland' ) );

		$section = static function ( string $heading, array $body ) use ( &$out, $skip ): void {
			if ( in_array( $heading, $skip, true ) ) {
				return;
			}
			$out[] = self::p( 'HEADING_1', $heading );
			array_push( $out, ...$body );
		};

		$section(
			'Language inventory',
			array(
				self::text( 'Testlandic (tst) and Valley Sign Language (vsl) are spoken here.' ),
				self::sources( 'https://example.org/inventory' ),
			)
		);
		$section(
			'Historical context',
			array(
				self::text( 'Testlandic became official in 1901.' ),
				self::sources( 'https://example.org/history-a', 'https://example.org/history-b' ),
			)
		);
		$section(
			'Current political context',
			array(
				self::text( 'Testland is a parliamentary republic.' ),
				self::sources( 'https://example.org/politics' ),
			)
		);

		$dimensions = array();
		foreach ( $questions as $key => $question ) {
			if ( null === $question ) {
				continue;
			}
			$dimensions[] = self::p(
				'NORMAL_TEXT',
				array(
					'text' => $question,
					'bold' => true,
				)
			);
			if ( null !== $answers[ $key ] ) {
				$dimensions[] = self::text( 'Your answer: ' . $answers[ $key ] );
			}
			$dimensions[] = self::text( 'Evidence for ' . $key . '.' );
			$dimensions[] = self::sources( 'https://example.org/' . $key );
		}
		$section( 'Language rights dimensions', $dimensions );

		$section(
			'Overall Rating',
			array(
				self::text( 'Was the status quo ante negative, neutral, or positive? Your answer: ' . ( $o['ante'] ?? 'Positive' ) ),
				self::text( 'Under the current de facto government or regime, are politics trending negatively, neutrally, or positively for language rights? Your answer: ' . ( $o['trend'] ?? 'Negative' ) ),
				self::p(
					'NORMAL_TEXT',
					array(
						'text' => $o['headline'] ?? 'Testland’s language rights have narrowed since 2020.',
						'bold' => true,
					)
				),
				self::text( 'The baseline was broad recognition.' ),
				self::sources( 'https://example.org/rating' ),
			)
		);

		$section( 'Testimonies', ( $o['testimonies'] ?? true ) ? array( self::text( 'A teacher described the change.' ) ) : array() );

		return $out;
	}

	/** A WordPress tab with a story card. */
	public static function wordpress_tab( string $body = 'Schools in the valley now teach Testlandic.' ): array {
		return array(
			self::p( 'HEADING_1', 'Testland' ),
			self::p( 'HEADING_2', 'A new school opens' ),
			self::text( $body ),
			self::p(
				'NORMAL_TEXT',
				array(
					'text' => 'School news',
					'url'  => 'https://example.org/school',
				),
				' · ',
				array(
					'text' => 'Ministry record',
					'url'  => 'https://example.org/ministry',
				)
			),
		);
	}

	/**
	 * A `documents.get` response with tabs.
	 *
	 * @param list<array<mixed>>      $report    Report tab elements.
	 * @param list<array<mixed>>|null $wordpress WordPress tab elements, or null for none.
	 */
	public static function document( array $report, ?array $wordpress = null ): array {
		$tabs = array( self::tab( 'Tab 1', $report ) );
		if ( null !== $wordpress ) {
			$tabs[] = self::tab( 'WordPress', $wordpress );
		}
		return array(
			'title' => 'Testland: language rights research documentation',
			'tabs'  => $tabs,
		);
	}

	/** One API tab. */
	public static function tab( string $title, array $content ): array {
		return array(
			'tabProperties' => array( 'title' => $title ),
			'documentTab'   => array( 'body' => array( 'content' => $content ) ),
		);
	}

	/** Parses a document with the real structure. */
	public static function parse( array $document ): array {
		return ( new \WT\Rights\Doc_Parser( wt_rights_structure() ) )->parse( \WT\Rights\Docs_Adapter::tabs( $document ) );
	}

	/** The codes of a parse result's problems. */
	public static function codes( array $problems ): array {
		return array_column( $problems, 'code' );
	}
}
