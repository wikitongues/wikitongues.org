<?php
/**
 * Renderer — turns a built map SVG into one language's map.
 *
 * A built SVG serves every language with the same set of places, so it has no
 * per-language styling. This class adds it, with the HTML API (never regular
 * expressions):
 *
 *   - `primary` or `secondary` on every shape and ring tagged `data-place`;
 *   - on a contested area tagged `data-claimants`, `hatch-claimed` becomes
 *     `hatch-primary` or `hatch-secondary` after the strongest claimant;
 *   - relative image hrefs (`shade/<key>.webp`) become absolute;
 *   - the `<title>` and `<desc>` text name this language and its places.
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
	public const VERSION = '2';

	public const STYLES = array(
		'light-focused' => 'Light focused',
		'light-full'    => 'Light full',
		'dark-full'     => 'Dark full',
		'dark-focused'  => 'Dark focused',
	);

	public const DEFAULT_STYLE = 'light-focused';

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
	 * The map's description: where it is centred and what else is marked.
	 *
	 * With `$max`, a role with more places than that is counted instead of
	 * listed, for the visible caption: the full list is in the sidebar already.
	 * Names may carry a leading "the" (wt_prefix_the()); it is ignored in sorting.
	 *
	 * @param array<string, string> $roles Place record ID → role.
	 * @param array<string, string> $names Place record ID → name.
	 * @param int                   $max   Most places to name per role.
	 * @return string
	 */
	public static function describe( array $roles, array $names, int $max = PHP_INT_MAX ): string {
		$by_role = array(
			'primary'   => array(),
			'secondary' => array(),
		);
		foreach ( $roles as $place => $role ) {
			if ( isset( $names[ $place ] ) && '' !== $names[ $place ] ) {
				$by_role[ $role ][] = $names[ $place ];
			}
		}
		$sort_key = static fn( string $name ): string => (string) preg_replace( '/^the /', '', $name );
		foreach ( $by_role as $role => $list ) {
			usort( $list, static fn( string $a, string $b ): int => strcmp( $sort_key( $a ), $sort_key( $b ) ) );
			$by_role[ $role ] = $list;
		}

		$primary   = $by_role['primary'];
		$secondary = $by_role['secondary'];
		if ( array() === $primary ) {
			return '';
		}
		if ( count( $primary ) > $max ) {
			return sprintf( 'Map of the %d places where it is spoken.', count( $primary ) + count( $secondary ) );
		}

		$text = 'Map centred on ' . self::list_names( $primary );
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
	 * @param array<string, string> $roles Place record ID → role.
	 * @param array<string, string> $names Place record ID → name.
	 * @return string
	 */
	public static function caption( array $roles, array $names ): string {
		return self::describe( $roles, $names, 3 );
	}

	/**
	 * Decorate a built SVG for one language.
	 *
	 * @param string                $svg      The built SVG.
	 * @param array<string, string> $roles    Place record ID → 'primary' | 'secondary'.
	 * @param string                $base_url URL of the maps/ folder, with a trailing slash.
	 * @param string                $title    Text for `<title>`.
	 * @param string                $desc     Text for `<desc>`; '' keeps the built one.
	 * @return string
	 */
	public static function decorate( string $svg, array $roles, string $base_url, string $title, string $desc ): string {
		$p       = new \WP_HTML_Tag_Processor( $svg );
		$in_desc = false;

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

			if ( 'TITLE' === $token && '' !== $title ) {
				$p->set_modifiable_text( $title );
				continue;
			}
			if ( 'DESC' === $token ) {
				$in_desc = true;
				continue;
			}

			$place = $p->get_attribute( 'data-place' );
			if ( is_string( $place ) && isset( $roles[ $place ] ) ) {
				$p->add_class( $roles[ $place ] );
			}

			$claimants = $p->get_attribute( 'data-claimants' );
			if ( is_string( $claimants ) ) {
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
