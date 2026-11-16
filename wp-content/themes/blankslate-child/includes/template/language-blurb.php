<?php
/**
 * Language blurb: a one-paragraph summary at the top of each language page.
 *
 * Example: "Emilian, original to Italy, is a dormant Indo-European language
 * written with the Latin script. In the language itself, it is called
 * Emigliân. Also known as Bolognese, Ferrarese, and Modenese, among other
 * names."
 *
 * Two layers, kept apart on purpose:
 *
 * - wt_language_facts() is the only part that reads WordPress. It flattens a
 *   language post into a plain array of strings.
 * - wt_language_blurb() turns that array into prose. It never calls WordPress,
 *   so it is unit-tested with plain arrays.
 *
 * Every phrase is optional. A builder returns '' when its fact is missing and
 * the sentence closes around the gap, so there is no branching on which
 * combination of fields a language happens to have. Wording that depends on a
 * value (EGIDS levels, genealogy terms that are not families, scripts that
 * are not scripts) lives in the lookup tables below, not in conditionals.
 *
 * To add a source later (Wikipedia summary, speaker population): add a key in
 * wt_language_facts(), write one builder, and add it to wt_language_blurb().
 *
 * @package blankslate-child
 */

/**
 * Most alternate names listed before "among other names".
 */
const WT_BLURB_MAX_ALT_NAMES = 3;

/**
 * Most "also spoken in" territories listed before "and N other territories".
 */
const WT_BLURB_MAX_TERRITORIES = 5;

/**
 * Most endonyms listed in the "called …" sentence.
 */
const WT_BLURB_MAX_ENDONYMS = 3;

/**
 * EGIDS status → the word used in the sentence.
 *
 * Levels 1–5 describe institutional use rather than vitality and read oddly as
 * an adjective ("a provincial language"), so they are left out, as are
 * "More data needed" and anything not listed here.
 *
 * @return array<string, string>
 */
function wt_blurb_status_words(): array {
	return array(
		'6a - Vigorous'       => 'vigorous',
		'6b - Threatened'     => 'threatened',
		'7 - Shifting'        => 'shifting',
		'8a - Moribund'       => 'moribund',
		'8b - Nearly extinct' => 'nearly extinct',
		'9 - Dormant'         => 'dormant',
		'10 - Extinct'        => 'extinct',
	);
}

/**
 * Genealogy terms that name a kind of language rather than a family.
 *
 * These replace the noun "language" instead of sitting in front of it, so
 * "Language isolate" gives "a language isolate", not "a Language isolate language".
 * Mapped to null: dropped from the sentence entirely.
 *
 * @return array<string, string|null>
 */
function wt_blurb_family_nouns(): array {
	return array(
		'Sign language'    => 'sign language',
		'Language isolate' => 'language isolate',
		'Mixed language'   => 'mixed language',
		'Pidgin'           => 'pidgin',
		'Unclassified'     => null,
	);
}

/**
 * Writing-system terms left out of "written with the … script".
 *
 * Braille is a tactile code for a script, not a language's script. "Unwritten"
 * is handled separately: it becomes "with no established writing system".
 *
 * @return string[]
 */
function wt_blurb_excluded_scripts(): array {
	return array( 'Braille', 'Unwritten' );
}

/**
 * Country names that take "the" in running prose ("original to the United
 * States"), on top of the ones wt_prefix_the() already handles.
 *
 * Kept here rather than in wt_prefix_the(), which has many callers on
 * territory and fellow pages that would also change.
 *
 * @return string[]
 */
function wt_blurb_the_names(): array {
	return array(
		'Cape Verde Islands',
		'Central African Republic',
		'Comoros',
		'Cook Islands',
		'Czech Republic',
		'Democratic Republic of the Congo',
		'Dominican Republic',
		'Federated States of Micronesia',
		'Maldives',
		'Marshall Islands',
		'Netherlands',
		'Philippines',
		'Republic of the Congo',
		'Russian Federation',
		'Sahrawi Arab Democratic Republic',
		'Seychelles',
		'Solomon Islands',
		'United Arab Emirates',
		'United Kingdom',
		'United States',
	);
}

/**
 * A place name as it reads mid-sentence: "the United States", "Italy".
 *
 * @param string $name Territory name.
 * @return string
 */
function wt_blurb_place( string $name ): string {
	return in_array( $name, wt_blurb_the_names(), true ) ? 'the ' . $name : wt_prefix_the( $name );
}

/**
 * Gather the facts the blurb is built from.
 *
 * @param int $post_id Language post ID.
 * @return array{name: string, origins: string[], status: string, families: string[], scripts: string[], territories: string[], alt_names: string[], endonyms: string[]}
 */
function wt_language_facts( int $post_id ): array {
	$name = wt_blurb_field( 'standard_name', $post_id );
	if ( '' === $name ) {
		$name = get_the_title( $post_id );
	}

	$territories = function_exists( 'get_field' ) ? get_field( 'territories', $post_id ) : array();
	$territories = is_array( $territories ) ? $territories : array();

	return array(
		'name'        => $name,
		'origins'     => wt_blurb_split( wt_blurb_field( 'nations_of_origin', $post_id ) ),
		'status'      => wt_blurb_field( 'egids_status', $post_id ),
		'families'    => wt_blurb_term_names( $post_id, 'linguistic-genealogy' ),
		'scripts'     => wt_blurb_term_names( $post_id, 'writing-system' ),
		'territories' => array_map( 'get_the_title', $territories ),
		'alt_names'   => wt_blurb_split( wt_blurb_field( 'alternate_names', $post_id ) ),
		'endonyms'    => wt_blurb_split( wt_blurb_field( 'autonym', $post_id ) ),
	);
}

/**
 * A text ACF field as a string, or '' when ACF is not loaded.
 *
 * @param string $name    Field name.
 * @param int    $post_id Post ID.
 * @return string
 */
function wt_blurb_field( string $name, int $post_id ): string {
	return function_exists( 'get_field' ) ? (string) get_field( $name, $post_id ) : '';
}

/**
 * Term names for a post, or an empty array.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy slug.
 * @return string[]
 */
function wt_blurb_term_names( int $post_id, string $taxonomy ): array {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! is_array( $terms ) ) {
		return array();
	}
	return array_map(
		function ( $term ) {
			return $term->name;
		},
		$terms
	);
}

/**
 * Build the blurb from a facts array.
 *
 * Returns '' when there is nothing to say beyond the name: "Emilian is a
 * language." is not worth showing.
 *
 * @param array<string, mixed> $facts See wt_language_facts().
 * @return string Plain text; escape on output.
 */
function wt_language_blurb( array $facts ): string {
	$identity = wt_blurb_identity_sentence( $facts );
	if ( '' === $identity ) {
		return '';
	}

	$sentences = array(
		$identity,
		wt_blurb_endonym_sentence( $facts ),
		wt_blurb_also_spoken_sentence( $facts ),
		wt_blurb_also_known_sentence( $facts ),
	);

	return implode( ' ', array_filter( $sentences ) );
}

/**
 * "Emilian, original to Italy, is a dormant Indo-European language written
 * with the Latin script."
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string Sentence, or '' when only the name is known.
 */
function wt_blurb_identity_sentence( array $facts ): string {
	$name = trim( (string) ( $facts['name'] ?? '' ) );
	if ( '' === $name ) {
		return '';
	}

	$origin = wt_blurb_origin_phrase( $facts );
	$status = wt_blurb_status_word( $facts );
	$kind   = wt_blurb_kind_phrase( $facts );
	$script = wt_blurb_script_phrase( $facts );

	if ( '' === $origin && '' === $status && 'language' === $kind && '' === $script ) {
		return '';
	}

	$noun_phrase = trim( $status . ' ' . $kind );
	$subject     = '' === $origin ? $name : $name . ', ' . $origin . ',';
	$predicate   = wt_blurb_article( $noun_phrase ) . ' ' . $noun_phrase;

	return $subject . ' is ' . $predicate . ( '' === $script ? '' : ' ' . $script ) . '.';
}

/**
 * "original to Italy" / "original to Georgia and Abkhazia".
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string
 */
function wt_blurb_origin_phrase( array $facts ): string {
	$origins = wt_blurb_clean_list( $facts['origins'] ?? array() );
	if ( ! $origins ) {
		return '';
	}
	return 'original to ' . wt_join_list( array_map( 'wt_blurb_place', $origins ) );
}

/**
 * EGIDS status as a single word, or '' when it is not one we describe.
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string
 */
function wt_blurb_status_word( array $facts ): string {
	$words = wt_blurb_status_words();
	return $words[ trim( (string) ( $facts['status'] ?? '' ) ) ] ?? '';
}

/**
 * The family plus noun: "Indo-European language", "language isolate", or
 * plain "language" when no family is known.
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string
 */
function wt_blurb_kind_phrase( array $facts ): string {
	$nouns    = wt_blurb_family_nouns();
	$noun     = 'language';
	$families = array();

	foreach ( wt_blurb_clean_list( $facts['families'] ?? array() ) as $family ) {
		if ( ! array_key_exists( $family, $nouns ) ) {
			$families[] = $family;
		} elseif ( null !== $nouns[ $family ] && 'language' === $noun ) {
			$noun = $nouns[ $family ];
		}
	}

	return trim( implode( ' and ', $families ) . ' ' . $noun );
}

/**
 * "written with the Latin script", "written with the Latin and Cyrillic
 * scripts", or "with no established writing system".
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string
 */
function wt_blurb_script_phrase( array $facts ): string {
	$all     = wt_blurb_clean_list( $facts['scripts'] ?? array() );
	$scripts = array_values( array_diff( $all, wt_blurb_excluded_scripts() ) );

	if ( $scripts ) {
		$noun = count( $scripts ) > 1 ? 'scripts' : 'script';
		return 'written with the ' . wt_join_list( $scripts ) . ' ' . $noun;
	}
	if ( in_array( 'Unwritten', $all, true ) ) {
		return 'with no established writing system';
	}
	return '';
}

/**
 * "Also spoken in France and Switzerland." — territories that are not also
 * countries of origin. Without a country of origin it reads "Spoken in …".
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string
 */
function wt_blurb_also_spoken_sentence( array $facts ): string {
	$origins     = wt_blurb_clean_list( $facts['origins'] ?? array() );
	$territories = array_values(
		array_diff( wt_blurb_clean_list( $facts['territories'] ?? array() ), $origins )
	);
	if ( ! $territories ) {
		return '';
	}

	$shown = array_map( 'wt_blurb_place', array_slice( $territories, 0, WT_BLURB_MAX_TERRITORIES ) );
	$rest  = count( $territories ) - count( $shown );
	if ( $rest > 0 ) {
		$shown[] = $rest . ' other ' . ( 1 === $rest ? 'territory' : 'territories' );
	}

	$lead = $origins ? 'Also spoken in ' : 'Spoken in ';
	return $lead . wt_join_list( $shown ) . '.';
}

/**
 * "In the language itself, it is called Emigliân." Endonyms keep their
 * bracketed romanization: "भोजपुरी (Bhōjpurī)". An endonym that is just the
 * standard name again is skipped.
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string
 */
function wt_blurb_endonym_sentence( array $facts ): string {
	$endonyms = wt_blurb_endonyms( $facts );
	if ( ! $endonyms ) {
		return '';
	}
	return 'In the language itself, it is called '
		. wt_join_list( array_slice( $endonyms, 0, WT_BLURB_MAX_ENDONYMS ), 'or' ) . '.';
}

/**
 * Endonyms worth showing: cleaned, and without any that repeat the name.
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string[]
 */
function wt_blurb_endonyms( array $facts ): array {
	$name = wt_blurb_name_key( (string) ( $facts['name'] ?? '' ) );
	return array_values(
		array_filter(
			wt_blurb_clean_list( $facts['endonyms'] ?? array(), false ),
			function ( $endonym ) use ( $name ) {
				return wt_blurb_name_key( $endonym ) !== $name;
			}
		)
	);
}

/**
 * A name reduced for comparison: lower case, no "(…)" qualifier, no
 * direction marks. "Aighon" and "aighon (North)" compare equal.
 *
 * @param string $name Name.
 * @return string
 */
function wt_blurb_name_key( string $name ): string {
	$name = (string) preg_replace( '/\s*\([^)]*\)|[\x{200E}\x{200F}]/u', '', $name );
	return mb_strtolower( trim( $name ) );
}

/**
 * "Also known as Bolognese, Ferrarese, and Modenese, among other names."
 *
 * @param array<string, mixed> $facts Facts array.
 * @return string
 */
function wt_blurb_also_known_sentence( array $facts ): string {
	// The name and the endonyms already appear earlier in the summary.
	$said  = array_map( 'wt_blurb_name_key', array_merge( array( (string) ( $facts['name'] ?? '' ) ), wt_blurb_endonyms( $facts ) ) );
	$names = array_values(
		array_filter(
			wt_blurb_clean_list( $facts['alt_names'] ?? array(), false ),
			function ( $alt ) use ( $said ) {
				// Names the data marks as pejorative stay in the metadata list,
				// but are never featured in the summary.
				return ! in_array( wt_blurb_name_key( $alt ), $said, true ) && false === stripos( $alt, '(pej' );
			}
		)
	);
	if ( ! $names ) {
		return '';
	}

	$shown = array_slice( $names, 0, WT_BLURB_MAX_ALT_NAMES );
	$more  = count( $names ) > count( $shown ) ? ', among other names' : '';

	return 'Also known as ' . wt_join_list( $shown ) . $more . '.';
}

/**
 * "a" or "an" for the phrase that follows.
 *
 * Goes by spelling, with the common "yoo"-sounding exceptions
 * ("a Uralic language", "a Uto-Aztecan language").
 *
 * @param string $phrase Phrase the article precedes.
 * @return string
 */
function wt_blurb_article( string $phrase ): string {
	if ( preg_match( '/^(uni|ura|uto|use|eu)/i', $phrase ) ) {
		return 'a';
	}
	return preg_match( '/^[aeiou]/i', $phrase ) ? 'an' : 'a';
}

/**
 * Split a comma-separated text field into trimmed, non-empty items.
 *
 * Commas inside brackets don't split: "Хальмг (Xaľmg), хальмг келн (Xaľmg keln)"
 * gives two items.
 *
 * @param string $value Field value.
 * @return string[]
 */
function wt_blurb_split( string $value ): array {
	return array_values(
		array_filter(
			array_map( 'trim', (array) preg_split( '/,(?![^()]*\))/', $value ) ),
			function ( $item ) {
				return '' !== $item;
			}
		)
	);
}

/**
 * Trim items, drop empties and duplicates, and (by default) strip bracketed
 * qualifiers: "Han (Hanzi)" → "Han".
 *
 * @param mixed $items          List of strings.
 * @param bool  $strip_brackets Whether to remove "(…)" qualifiers.
 * @return string[]
 */
function wt_blurb_clean_list( $items, bool $strip_brackets = true ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}
	$clean = array();
	foreach ( $items as $item ) {
		$item = trim( (string) $item );
		if ( $strip_brackets ) {
			$item = trim( (string) preg_replace( '/\s*\([^)]*\)/', '', $item ) );
		}
		if ( '' !== $item && ! in_array( $item, $clean, true ) ) {
			$clean[] = $item;
		}
	}
	return $clean;
}
