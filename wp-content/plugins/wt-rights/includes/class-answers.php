<?php
/**
 * Answers — normalizes what researchers type after "Your answer:".
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Turns "Yes.", " UNCLEAR ", "N/A" into the stored form ("yes", "unclear",
 * "not applicable"), so a Doc and its Airtable row compare equal however the
 * answer was typed.
 */
final class Answers {

	private const ALIASES = array(
		'n/a'            => 'not applicable',
		'na'             => 'not applicable',
		'not-applicable' => 'not applicable',
		'postitive'      => 'positive', // The template's own typo.
	);

	/**
	 * The normalized answer, or '' when nothing is there.
	 *
	 * @param string $raw The text after "Your answer:", or an Airtable option name.
	 */
	public static function normalize( string $raw ): string {
		$answer = strtolower( trim( $raw ) );
		$answer = rtrim( $answer, " \t.;:!" );
		$answer = (string) preg_replace( '/\s+/', ' ', $answer );
		return self::ALIASES[ $answer ] ?? $answer;
	}

	/**
	 * The text after the last "Your answer:" in a line, or null when the line has none.
	 *
	 * @param string $line A paragraph's text.
	 */
	public static function from_line( string $line ): ?string {
		if ( ! preg_match( '/your answer\s*:\s*(.*)$/iu', $line, $m ) ) {
			return null;
		}
		return self::normalize( $m[1] );
	}
}
