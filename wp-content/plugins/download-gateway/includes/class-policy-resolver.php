<?php
/**
 * PolicyResolver — determines the gate policy for a downloadable post.
 *
 * Precedence (highest to lowest):
 *   1. Per-resource postmeta  (_gateway_gate_policy on the post itself)
 *   2. Taxonomy-level default (term meta — wired but inactive until sub-phase 4
 *                              adds ACF fields; resolve() skips this tier if no
 *                              registered taxonomy returns a value)
 *   3. Global site default    (SettingsRepository::get_global_gate_policy())
 *
 * Return value is always one of: 'none', 'soft', 'hard'.
 *
 * Usage:
 *   $policy = PolicyResolver::resolve( $post_id );
 *   // 'none' → issue token, no gate
 *   // 'soft' → show skippable gate modal
 *   // 'hard' → email required before token is issued
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class PolicyResolver {

	/** Postmeta key for per-resource gate policy overrides. */
	const META_KEY = '_gateway_gate_policy';

	/**
	 * Resolve the effective gate policy for a given post ID.
	 *
	 * @param int $post_id Post ID of the downloadable item.
	 * @return string 'none'|'soft'|'hard'
	 */
	public static function resolve( int $post_id ): string {
		// Tier 1 — per-resource override.
		$per_resource = self::resolve_per_resource( $post_id );
		if ( null !== $per_resource ) {
			Logger::debug( "PolicyResolver: per-resource policy '{$per_resource}' for post {$post_id}." );
			return $per_resource;
		}

		// Tier 2 — taxonomy default.
		$taxonomy = self::resolve_taxonomy( $post_id );
		if ( null !== $taxonomy ) {
			Logger::debug( "PolicyResolver: taxonomy policy '{$taxonomy}' for post {$post_id}." );
			return $taxonomy;
		}

		// Tier 3 — global default.
		$global = SettingsRepository::get_global_gate_policy();
		Logger::debug( "PolicyResolver: global policy '{$global}' for post {$post_id}." );
		return $global;
	}

	// -------------------------------------------------------------------------
	// Private resolution tiers
	// -------------------------------------------------------------------------

	/**
	 * Returns the per-resource policy if explicitly set, or null to fall through.
	 */
	private static function resolve_per_resource( int $post_id ): ?string {
		$value = get_post_meta( $post_id, self::META_KEY, true );
		return self::validate_policy( $value );
	}

	/**
	 * Returns a taxonomy-level policy if any registered taxonomy on this post
	 * has a gateway_gate_policy term meta value set, or null to fall through.
	 *
	 * Currently inactive — no taxonomy has gateway gate meta set until sub-phase 4
	 * ACF fields are added. The tier is wired here so precedence is correct from
	 * the start; no code changes needed in sub-phase 4 beyond writing the term meta.
	 */
	private static function resolve_taxonomy( int $post_id ): ?string {
		$post_type  = get_post_type( $post_id );
		$taxonomies = get_object_taxonomies( $post_type );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$value = get_term_meta( $term->term_id, 'gateway_gate_policy', true );
				$valid = self::validate_policy( $value );
				if ( null !== $valid ) {
					return $valid;
				}
			}
		}

		return null;
	}

	/**
	 * Returns the value if it is a valid policy string, or null otherwise.
	 *
	 * @param mixed $value Raw value from postmeta or term meta.
	 */
	private static function validate_policy( mixed $value ): ?string {
		$valid = [ SettingsRepository::POLICY_NONE, SettingsRepository::POLICY_SOFT, SettingsRepository::POLICY_HARD ];
		return ( is_string( $value ) && in_array( $value, $valid, true ) ) ? $value : null;
	}
}
