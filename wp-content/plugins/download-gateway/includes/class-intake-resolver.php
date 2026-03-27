<?php
/**
 * IntakeResolver — 3-tier resolution of intake form set and always-show flag.
 *
 * Follows the same pattern as PolicyResolver:
 *   per-record postmeta → per-CPT wp_option → global wp_option
 *
 * Returns an array:
 *   'set'    — named set key registered via gateway_intake_fields filter,
 *              or empty string meaning no intake form.
 *   'always' — bool; when true, intake shows on passthrough (repeat downloads
 *              within the same browser session) as well as first-time gate.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class IntakeResolver {

	/** Postmeta key for per-resource intake set override. */
	const META_KEY_SET = '_gateway_intake_set';

	/** Postmeta key for per-resource always-show override. */
	const META_KEY_ALWAYS = '_gateway_intake_always';

	/**
	 * Resolve intake configuration for a given post.
	 *
	 * @param int $post_id Post ID of the downloadable resource.
	 * @return array{ set: string, always: bool }
	 */
	public static function resolve( int $post_id ): array {
		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return array(
				'set'    => '',
				'always' => false,
			);
		}

		return array(
			'set'    => self::resolve_set( $post_id, $post_type ),
			'always' => self::resolve_always( $post_id, $post_type ),
		);
	}

	/**
	 * Resolve the intake field set name for a post.
	 *
	 * Returns the set name string, or empty string if no intake should be shown.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private static function resolve_set( int $post_id, string $post_type ): string {
		// Tier 1: per-resource postmeta.
		$meta = (string) get_post_meta( $post_id, self::META_KEY_SET, true );
		if ( 'none' === $meta ) {
			return '';
		}
		if ( '' !== $meta && 'inherit' !== $meta ) {
			return $meta;
		}

		// Tier 2: per-CPT option.
		$cpt = SettingsRepository::get_cpt_intake_set( $post_type );
		if ( null !== $cpt ) {
			return 'none' === $cpt ? '' : $cpt;
		}

		// Tier 3: global option.
		$global = SettingsRepository::get_global_intake_set();
		return 'none' === $global ? '' : $global;
	}

	/**
	 * Resolve the always-show flag for a post.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	private static function resolve_always( int $post_id, string $post_type ): bool {
		// Tier 1: per-resource postmeta.
		$meta = (string) get_post_meta( $post_id, self::META_KEY_ALWAYS, true );
		if ( '1' === $meta ) {
			return true;
		}
		if ( '0' === $meta ) {
			return false;
		}
		// '' or 'inherit' → fall through.

		// Tier 2: per-CPT option.
		$cpt = SettingsRepository::get_cpt_intake_always( $post_type );
		if ( null !== $cpt ) {
			return $cpt;
		}

		// Tier 3: global option.
		return SettingsRepository::get_global_intake_always();
	}
}
