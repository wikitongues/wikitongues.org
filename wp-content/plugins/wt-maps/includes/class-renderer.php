<?php
/**
 * Renderer — turns a built map SVG into one language's map.
 *
 * A built SVG serves every language with the same set of places, so it has no
 * per-language styling. This class adds it, with the HTML API (never regular
 * expressions):
 *
 *   - `primary` or `secondary` on every shape and ring tagged `data-place`;
 *   - on an active contested area (`hatch-claimed`), that class becomes
 *     `hatch-primary` or `hatch-secondary` after its strongest claimant on the
 *     map (`data-claimants` holds every claimant, on the map or not);
 *   - relative image hrefs (`shade/<key>.webp`) become absolute;
 *   - the map's accessible name (`aria-label`) and `<desc>` name this language
 *     and its places. The `<title>` is emptied: browsers show an SVG title as a
 *     tooltip over every shape, which talked over the hover labels (2026-11-24).
 *
 * The role rule (plan 3.2): places that are also the language's countries of
 * origin are primary and the rest secondary; if none is a country of origin,
 * every place is primary. A focused map (Hawaiian → Hawaii) draws places that
 * aren't countries of origin, so they are all primary.
 *
 * No WordPress calls apart from the HTML API, so it is unit-tested.
 *
 * @package WT\Maps
 */

namespace WT\Maps;

final class Renderer {

	/**
	 * Bumped when decorate() changes its output, so cached maps are rebuilt.
	 */
	public const VERSION = '6';

	/**
	 * The line under a map that shows an active contested area (claims spec,
	 * Disclaimer).
	 */
	public const DISCLAIMER = 'Borders and contested areas are shown for reference and do not imply a position on sovereignty.';

	/**
	 * Whether a built map shows an active contested area: one a highlighted
	 * place claims (`hatch`: `hatch-claimed` as built, a role class once
	 * decorated), or one a highlighted place is (its dashed `edge`: Taiwan on
	 * its own map). Not `data-claimants`: every contested area carries its
	 * claimants, active or not (claims on hover). Works before and after
	 * decorate(), which wt_map_places() calls first.
	 *
	 * @param string $svg The built SVG.
	 * @return bool
	 */
	public static function shows_contested( string $svg ): bool {
		$p = new \WP_HTML_Tag_Processor( $svg );
		while ( $p->next_tag( array( 'class_name' => 'disputed' ) ) ) {
			if ( $p->has_class( 'hatch' ) || $p->has_class( 'edge' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Primary or secondary for each place.
	 *
	 * @param list<string> $places  Place record IDs on the map.
	 * @param list<string> $origins Record IDs of the language's countries of origin.
	 * @return array<string, string> Place record ID → 'primary' | 'secondary', in the order of $places.
	 */
	public static function roles( array $places, array $origins ): array {
		$any_origin = array() !== array_intersect( $places, $origins );
		$roles      = array();
		foreach ( $places as $place ) {
			$roles[ $place ] = ! $any_origin || in_array( $place, $origins, true ) ? 'primary' : 'secondary';
		}
		return $roles;
	}

	/**
	 * The map's description: the places it shows, and what else is marked.
	 *
	 * With `$max`, the visible caption: past that many places in a role, the
	 * rest are counted instead of listed, since the sidebar lists them all.
	 * Origins come first, in the order the language lists them (French: France,
	 * then Lebanon, …); the other places follow alphabetically, ignoring a
	 * leading "the" (wt_prefix_the()).
	 *
	 * @param array<string, string> $roles   Place record ID → role.
	 * @param array<string, string> $names   Place record ID → name.
	 * @param int                   $max     Most places to name per role.
	 * @param list<string>          $origins The language's origin record IDs, in its order.
	 * @return string
	 */
	public static function describe( array $roles, array $names, int $max = PHP_INT_MAX, array $origins = array() ): string {
		$by_role = array(
			'primary'   => array(),
			'secondary' => array(),
		);
		$rank    = array_flip( $origins );
		$alpha   = static fn( string $name ): string => (string) preg_replace( '/^the /', '', $name );
		$order   = static function ( string $a, string $b ) use ( $rank, $names, $alpha ): int {
			$ra = $rank[ $a ] ?? PHP_INT_MAX;
			$rb = $rank[ $b ] ?? PHP_INT_MAX;
			return $ra <=> $rb ?: strcmp( $alpha( $names[ $a ] ), $alpha( $names[ $b ] ) );
		};
		foreach ( $roles as $place => $role ) {
			if ( isset( $names[ $place ] ) && '' !== $names[ $place ] ) {
				$by_role[ $role ][] = (string) $place;
			}
		}
		foreach ( $by_role as $role => $places ) {
			usort( $places, $order );
			$by_role[ $role ] = array_map( static fn( string $place ): string => $names[ $place ], $places );
		}

		$primary   = $by_role['primary'];
		$secondary = $by_role['secondary'];
		if ( array() === $primary ) {
			return '';
		}
		if ( count( $primary ) > $max ) {
			$rest = count( $primary ) - $max + count( $secondary );
			return 'Map showing ' . implode( ', ', array_slice( $primary, 0, $max ) ) . sprintf( ', and %d other places.', $rest );
		}

		$text = 'Map showing ' . self::list_names( $primary );
		if ( count( $secondary ) > $max ) {
			$text .= sprintf( ', with %d other places also marked', count( $secondary ) );
		} elseif ( array() !== $secondary ) {
			$text .= ', with ' . self::list_names( $secondary ) . ' also marked';
		}
		return $text . '.';
	}

	/**
	 * The visible caption: the description, naming at most three places per role.
	 *
	 * @param array<string, string> $roles   Place record ID → role.
	 * @param array<string, string> $names   Place record ID → name.
	 * @param list<string>          $origins The language's origin record IDs, in its order.
	 * @return string
	 */
	public static function caption( array $roles, array $names, array $origins = array() ): string {
		return self::describe( $roles, $names, 3, $origins );
	}

	/**
	 * Decorate a built SVG for one language.
	 *
	 * @param string                $svg      The built SVG.
	 * @param array<string, string> $roles    Place record ID → 'primary' | 'secondary'.
	 * @param string                $base_url URL of the maps/ folder, with a trailing slash.
	 * @param string                $title    The map's accessible name (`aria-label`); '' keeps the built `<title>`.
	 * @param string                $desc     Text for `<desc>`; '' keeps the built one.
	 * @param float                 $inset    Scale for the locator globe, anchored at its
	 *                                        frame corner so the margin grows with it (1: as built).
	 * @return string
	 */
	public static function decorate( string $svg, array $roles, string $base_url, string $title, string $desc, float $inset = 1.0 ): string {
		$anchor  = 1.0 !== $inset ? self::inset_anchor( $svg ) : null;
		$p       = new \WP_HTML_Tag_Processor( $svg );
		$in_desc = false;
		$root    = true;

		while ( $p->next_token() ) {
			$token = $p->get_token_name();

			if ( '#text' === $token ) {
				if ( $in_desc && '' !== $desc ) {
					$p->set_modifiable_text( $desc );
				}
				$in_desc = false;
				continue;
			}
			if ( '#tag' !== $p->get_token_type() ) {
				continue;
			}
			if ( $p->is_tag_closer() ) {
				$in_desc = false;
				continue;
			}

			// The name goes on the <svg> as aria-label, and the <desc> stays its
			// description; the <title> is emptied so no tooltip shows on hover.
			if ( $root && 'SVG' === $token ) {
				$root = false;
				if ( '' !== $title ) {
					$labelled = $p->get_attribute( 'aria-labelledby' );
					$p->remove_attribute( 'aria-labelledby' );
					$p->set_attribute( 'aria-label', $title );
					$ids = is_string( $labelled ) ? explode( ' ', $labelled ) : array();
					if ( isset( $ids[1] ) ) {
						$p->set_attribute( 'aria-describedby', $ids[1] );
					}
				}
			}
			if ( 'TITLE' === $token && '' !== $title ) {
				$p->set_modifiable_text( '' );
				continue;
			}
			if ( 'DESC' === $token ) {
				$in_desc = true;
				continue;
			}

			if ( null !== $anchor && 'G' === $token && $p->has_class( 'inset' ) ) {
				$p->set_attribute(
					'transform',
					sprintf( 'translate(%s %s) scale(%s)', self::num( $anchor[0] * ( 1 - $inset ) ), self::num( $anchor[1] * ( 1 - $inset ) ), self::num( $inset ) )
				);
			}

			$place = $p->get_attribute( 'data-place' );
			if ( is_string( $place ) && isset( $roles[ $place ] ) ) {
				$p->add_class( $roles[ $place ] );
			}

			if ( $p->has_class( 'hatch-claimed' ) ) {
				$claimants = (string) $p->get_attribute( 'data-claimants' );
				$p->remove_class( 'hatch-claimed' );
				$p->add_class( 'hatch-' . self::strongest( explode( ' ', $claimants ), $roles ) );
			}

			if ( 'IMAGE' === $token ) {
				$href = $p->get_attribute( 'href' );
				if ( is_string( $href ) && self::is_relative( $href ) ) {
					$p->set_attribute( 'href', $base_url . $href );
				}
			}
		}

		return $p->get_updated_html();
	}

	/**
	 * The frame corner nearest the locator globe, in SVG units, or null when
	 * the map has no globe. The build puts it in the top-right, top-left or
	 * bottom-right corner of the 400×300 frame.
	 *
	 * @param string $svg The built SVG.
	 * @return array{0: float, 1: float}|null
	 */
	private static function inset_anchor( string $svg ): ?array {
		$p = new \WP_HTML_Tag_Processor( $svg );
		if ( ! $p->next_tag( array( 'class_name' => 'globe-halo' ) ) ) {
			return null;
		}
		$cx = (float) $p->get_attribute( 'cx' );
		$cy = (float) $p->get_attribute( 'cy' );
		return array( $cx > 200 ? 400.0 : 0.0, $cy > 150 ? 300.0 : 0.0 );
	}

	/**
	 * A number for an SVG attribute: up to three decimals, no trailing zeros.
	 *
	 * @param float $n The number.
	 * @return string
	 */
	private static function num( float $n ): string {
		$s = rtrim( rtrim( number_format( $n, 3, '.', '' ), '0' ), '.' );
		return '-0' === $s ? '0' : $s;
	}

	/**
	 * The strongest role among a contested area's claimants.
	 *
	 * @param list<string>          $claimants Place record IDs.
	 * @param array<string, string> $roles     Place record ID → role.
	 * @return string 'primary', 'secondary', or 'land' when no claimant is on this map.
	 */
	private static function strongest( array $claimants, array $roles ): string {
		$found = array_intersect_key( $roles, array_flip( $claimants ) );
		if ( in_array( 'primary', $found, true ) ) {
			return 'primary';
		}
		return array() === $found ? 'land' : 'secondary';
	}

	/**
	 * Whether an href points inside the maps/ folder.
	 *
	 * @param string $href Attribute value.
	 * @return bool
	 */
	private static function is_relative( string $href ): bool {
		return '' !== $href && 1 !== preg_match( '#^(?:[a-z][a-z0-9+.-]*:|/|\#)#i', $href );
	}

	/**
	 * "A", "A and B", "A, B, and C".
	 *
	 * @param list<string> $names Names.
	 * @return string
	 */
	private static function list_names( array $names ): string {
		if ( count( $names ) < 3 ) {
			return implode( ' and ', $names );
		}
		$last = array_pop( $names );
		return implode( ', ', $names ) . ', and ' . $last;
	}
}
