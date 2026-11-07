<?php
use WP_Mock\Tools\TestCase;

class LanguageBlurbTest extends TestCase {

	/**
	 * Emilian, as stored locally.
	 *
	 * @param array<string, mixed> $overrides Facts to replace.
	 * @return array<string, mixed>
	 */
	private function emilian( array $overrides = array() ): array {
		return array_merge(
			array(
				'name'        => 'Emilian',
				'origins'     => array( 'Italy' ),
				'status'      => '9 - Dormant',
				'families'    => array( 'Indo-European' ),
				'scripts'     => array( 'Latin' ),
				'territories' => array( 'Italy' ),
				'alt_names'   => array(),
			),
			$overrides
		);
	}

	public function test_full_identity_sentence() {
		$this->assertSame(
			'Emilian, original to Italy, is a dormant Indo-European language written with the Latin script.',
			wt_language_blurb( $this->emilian() )
		);
	}

	public function test_missing_facts_close_up() {
		$this->assertSame(
			'Emilian is an Indo-European language.',
			wt_language_blurb(
				$this->emilian(
					array(
						'origins'     => array(),
						'status'      => '',
						'scripts'     => array(),
						'territories' => array(),
					)
				)
			)
		);
	}

	public function test_name_only_gives_no_blurb() {
		$this->assertSame( '', wt_language_blurb( array( 'name' => 'Emilian' ) ) );
	}

	public function test_no_name_gives_no_blurb() {
		$this->assertSame( '', wt_language_blurb( $this->emilian( array( 'name' => '' ) ) ) );
	}

	public function test_undescribed_status_is_left_out() {
		$this->assertSame(
			'Emilian, original to Italy, is an Indo-European language written with the Latin script.',
			wt_language_blurb( $this->emilian( array( 'status' => 'More data needed' ) ) )
		);
		$this->assertSame(
			'Emilian, original to Italy, is an Indo-European language written with the Latin script.',
			wt_language_blurb( $this->emilian( array( 'status' => '1 - National' ) ) )
		);
	}

	public function test_article_follows_first_word() {
		$this->assertStringContainsString(
			'is an extinct Indo-European',
			wt_language_blurb( $this->emilian( array( 'status' => '10 - Extinct' ) ) )
		);
		$this->assertStringContainsString(
			'is a Uralic language',
			wt_language_blurb(
				$this->emilian(
					array(
						'status'   => '',
						'families' => array( 'Uralic' ),
					)
				)
			)
		);
	}

	public function test_family_that_names_a_kind_replaces_the_noun() {
		$this->assertStringContainsString(
			'is a threatened language isolate',
			wt_language_blurb(
				$this->emilian(
					array(
						'status'   => '6b - Threatened',
						'families' => array( 'Language isolate' ),
					)
				)
			)
		);
		$this->assertStringContainsString(
			'is a sign language',
			wt_language_blurb(
				$this->emilian(
					array(
						'status'   => '',
						'families' => array( 'Sign language' ),
					)
				)
			)
		);
	}

	public function test_unclassified_is_dropped() {
		$this->assertStringContainsString(
			'is a dormant language written',
			wt_language_blurb( $this->emilian( array( 'families' => array( 'Unclassified' ) ) ) )
		);
	}

	public function test_multiple_origins_use_oxford_comma_and_the() {
		$this->assertStringStartsWith(
			'Emilian, original to Italy, the Gambia, and the Bahamas,',
			wt_language_blurb(
				$this->emilian(
					array(
						'origins'     => array( 'Italy', 'Gambia', 'Bahamas' ),
						'territories' => array(),
					)
				)
			)
		);
	}

	public function test_multiple_scripts_and_brackets_stripped() {
		$this->assertStringContainsString(
			'written with the Han and Latin scripts.',
			wt_language_blurb( $this->emilian( array( 'scripts' => array( 'Han (Hanzi)', 'Han (Kanji)', 'Latin' ) ) ) )
		);
	}

	public function test_unwritten_language() {
		$this->assertStringEndsWith(
			'is a dormant Indo-European language with no established writing system.',
			wt_language_blurb( $this->emilian( array( 'scripts' => array( 'Unwritten' ) ) ) )
		);
	}

	public function test_unwritten_ignored_when_a_script_is_listed() {
		$this->assertStringEndsWith(
			'written with the Latin script.',
			wt_language_blurb( $this->emilian( array( 'scripts' => array( 'Unwritten', 'Latin' ) ) ) )
		);
	}

	public function test_braille_is_not_described_as_a_script() {
		$this->assertStringEndsWith(
			'is a dormant Indo-European language.',
			wt_language_blurb( $this->emilian( array( 'scripts' => array( 'Braille' ) ) ) )
		);
	}

	public function test_also_spoken_in_excludes_origins() {
		$this->assertStringEndsWith(
			'Latin script. Also spoken in San Marino and Switzerland.',
			wt_language_blurb( $this->emilian( array( 'territories' => array( 'Italy', 'San Marino', 'Switzerland' ) ) ) )
		);
	}

	public function test_spoken_in_without_origin_drops_also() {
		$this->assertSame(
			'Emilian is a dormant Indo-European language written with the Latin script. Spoken in Italy.',
			wt_language_blurb( $this->emilian( array( 'origins' => array() ) ) )
		);
	}

	public function test_also_spoken_in_is_capped() {
		$this->assertStringEndsWith(
			'Also spoken in A, B, C, D, E, and 2 other territories.',
			wt_language_blurb( $this->emilian( array( 'territories' => array( 'A', 'B', 'C', 'D', 'E', 'F', 'G' ) ) ) )
		);
		$this->assertStringEndsWith(
			'Also spoken in A, B, C, D, E, and 1 other territory.',
			wt_language_blurb( $this->emilian( array( 'territories' => array( 'A', 'B', 'C', 'D', 'E', 'F' ) ) ) )
		);
	}

	public function test_also_known_as_skips_own_name_and_caps() {
		$this->assertStringEndsWith(
			'Latin script. Also known as Bolognese, Ferrarese, and Modenese, among other names.',
			wt_language_blurb(
				$this->emilian(
					array( 'alt_names' => array( 'Emilian', 'Bolognese', 'Ferrarese', 'Modenese', 'Parmigiano' ) )
				)
			)
		);
		$this->assertStringEndsWith(
			'Also known as Emiliano.',
			wt_language_blurb( $this->emilian( array( 'alt_names' => array( 'emilian', 'Emiliano' ) ) ) )
		);
	}

	public function test_pejorative_names_are_never_featured() {
		$this->assertStringEndsWith(
			'Also known as Laramanik.',
			wt_language_blurb( $this->emilian( array( 'alt_names' => array( '"Dorobo" (pej.)', 'Laramanik' ) ) ) )
		);
		$this->assertStringEndsWith(
			'Latin script.',
			wt_language_blurb( $this->emilian( array( 'alt_names' => array( '"Ndorobo" (pej.)' ) ) ) )
		);
	}

	public function test_countries_that_take_the() {
		$this->assertStringStartsWith(
			'Kiowa, original to the United States,',
			wt_language_blurb(
				$this->emilian(
					array(
						'name'    => 'Kiowa',
						'origins' => array( 'United States' ),
					)
				)
			)
		);
		$this->assertStringEndsWith(
			'Also spoken in the Russian Federation.',
			wt_language_blurb( $this->emilian( array( 'territories' => array( 'Italy', 'Russian Federation' ) ) ) )
		);
	}

	public function test_endonym_sentence_follows_identity() {
		$this->assertSame(
			'Emilian, original to Italy, is a dormant Indo-European language written with the Latin script. In the language itself, it is called Emigliân.',
			wt_language_blurb( $this->emilian( array( 'endonyms' => array( 'Emigliân' ) ) ) )
		);
	}

	public function test_endonym_that_repeats_the_name_is_skipped() {
		$this->assertStringEndsWith(
			'Latin script.',
			wt_language_blurb( $this->emilian( array( 'endonyms' => array( 'emilian' ) ) ) )
		);
		$this->assertStringEndsWith(
			'it is called Apsokok.',
			wt_language_blurb(
				$this->emilian(
					array(
						'name'     => 'Aighon',
						'endonyms' => array( 'Aighon', 'Apsokok' ),
					)
				)
			)
		);
	}

	public function test_several_endonyms_use_or_and_keep_romanization() {
		$this->assertStringEndsWith(
			'it is called Хальмг (Xaľmg) or хальмг келн (Xaľmg keln).',
			wt_language_blurb(
				$this->emilian(
					array( 'endonyms' => wt_blurb_split( 'Хальмг (Xaľmg), хальмг келн (Xaľmg keln)' ) )
				)
			)
		);
	}

	public function test_endonyms_are_not_repeated_as_alternate_names() {
		$this->assertStringEndsWith(
			'it is called Emigliân. Also known as Bolognese.',
			wt_language_blurb(
				$this->emilian(
					array(
						'endonyms'  => array( 'Emigliân' ),
						'alt_names' => array( 'Emigliân', 'Bolognese' ),
					)
				)
			)
		);
	}

	public function test_endonym_alone_does_not_make_a_blurb() {
		$this->assertSame(
			'',
			wt_language_blurb(
				array(
					'name'     => 'Emilian',
					'endonyms' => array( 'Emigliân' ),
				)
			)
		);
	}

	public function test_split_ignores_commas_inside_brackets() {
		$this->assertSame(
			array( 'A (x, y)', 'B' ),
			wt_blurb_split( 'A (x, y), B' )
		);
	}

	public function test_split_trims_and_drops_empties() {
		$this->assertSame( array( 'Georgia', 'Abkhazia' ), wt_blurb_split( ' Georgia, ,Abkhazia ' ) );
		$this->assertSame( array(), wt_blurb_split( '' ) );
	}

	public function test_join_list() {
		$this->assertSame( '', wt_join_list( array() ) );
		$this->assertSame( 'A', wt_join_list( array( 'A' ) ) );
		$this->assertSame( 'A and B', wt_join_list( array( 'A', 'B' ) ) );
		$this->assertSame( 'A, B, and C', wt_join_list( array( 'A', 'B', 'C' ) ) );
		$this->assertSame( 'A, B, or C', wt_join_list( array( 'A', 'B', 'C' ), 'or' ) );
	}
}
