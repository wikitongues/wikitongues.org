<?php
/**
 * PolicyResolver — determines the effective gate policy for a downloadable post.
 *
 * Precedence (highest to lowest):
 *   1. Per-resource postmeta  (_gateway_gate_policy on the post itself)
 *   2. Per-CPT default        (gateway_cpt_policy_{post_type} option)
 *   3. Global site default    (SettingsRepository::get_global_gate_policy())
 *
 * Each tier returns null to signal "no override here — fall through to the
 * next tier". The global tier always returns a concrete value.
 *
 * Return value is one of: 'none' | 'soft' | 'hard' | 'disabled'.
 * - 'disabled' means the download affordance should be hidden entirely.
 * - 'none'     means issue a token with no gate.
 * - 'soft'     means show a skippable gate modal.
 * - 'hard'     means email is required before a token is issued.
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
	 * @return string 'none'|'soft'|'hard'|'disabled'
	 */
	public static function resolve( int $post_id ): string {
		// Tier 1 — per-resource override.
		$per_resource = self::resolve_per_resource( $post_id );
		if ( null !== $per_resource ) {
			Logger::debug( "PolicyResolver: per-resource policy '{$per_resource}' for post {$post_id}." );
			return $per_resource;
		}

		// Tier 2 — per-CPT default.
		$per_cpt = self::resolve_per_cpt( $post_id );
		if ( null !== $per_cpt ) {
			Logger::debug( "PolicyResolver: per-CPT policy '{$per_cpt}' for post {$post_id}." );
			return $per_cpt;
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
		return self::validate_concrete( $value );
	}

	/**
	 * Returns the per-CPT policy if set for this post's type, or null to fall through.
	 */
	private static function resolve_per_cpt( int $post_id ): ?string {
		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return null;
		}
		return SettingsRepository::get_cpt_policy( $post_type );
	}

	/**
	 * Returns the value if it is a concrete policy string, or null otherwise.
	 *
	 * @param mixed $value Raw value from postmeta or options.
	 */
	private static function validate_concrete( mixed $value ): ?string {
		return ( is_string( $value ) && in_array( $value, SettingsRepository::concrete_policies(), true ) )
			? $value
			: null;
	}
}
