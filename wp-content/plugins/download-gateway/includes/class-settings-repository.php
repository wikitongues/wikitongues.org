<?php
/**
 * SettingsRepository — typed read/write access to gateway wp_options.
 *
 * All options are stored under the `gateway_` prefix. Defaults are defined
 * here so callers never receive null — add a getter and a default constant
 * for every new setting added in future sub-phases.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class SettingsRepository {

	// Option keys.
	const OPTION_GLOBAL_GATE_POLICY = 'gateway_global_gate_policy';
	const OPTION_RETENTION_MONTHS   = 'gateway_retention_months';

	/** Option key pattern for per-CPT policy. Sprintf with post_type. */
	const OPTION_CPT_POLICY_PREFIX = 'gateway_cpt_policy_';

	// Valid gate policy values (concrete — used as effective resolved values).
	const POLICY_NONE     = 'none';
	const POLICY_SOFT     = 'soft';
	const POLICY_HARD     = 'hard';
	const POLICY_DISABLED = 'disabled';

	/** Sentinel value meaning "use the next tier down". Never returned by PolicyResolver::resolve(). */
	const POLICY_INHERIT = 'inherit';

	// Defaults.
	const DEFAULT_GLOBAL_GATE_POLICY = self::POLICY_NONE;
	const DEFAULT_RETENTION_MONTHS   = 24;

	/**
	 * All concrete policy values (valid as a resolved effective policy).
	 *
	 * @return string[]
	 */
	public static function concrete_policies(): array {
		return array( self::POLICY_NONE, self::POLICY_SOFT, self::POLICY_HARD, self::POLICY_DISABLED );
	}

	/**
	 * All values accepted as a stored override (concrete + inherit sentinel).
	 *
	 * @return string[]
	 */
	public static function allowed_override_values(): array {
		return array( self::POLICY_NONE, self::POLICY_SOFT, self::POLICY_HARD, self::POLICY_DISABLED, self::POLICY_INHERIT );
	}

	/**
	 * Returns the site-wide gate policy: 'none', 'soft', 'hard', or 'disabled'.
	 * Overridden per-CPT or per-resource by PolicyResolver.
	 */
	public static function get_global_gate_policy(): string {
		$value = get_option( self::OPTION_GLOBAL_GATE_POLICY, self::DEFAULT_GLOBAL_GATE_POLICY );
		return in_array( $value, self::concrete_policies(), true )
			? $value
			: self::DEFAULT_GLOBAL_GATE_POLICY;
	}

	/**
	 * Returns the per-CPT policy for the given post type, or null if not set
	 * (i.e. it inherits from the global default).
	 *
	 * @param string $post_type WordPress post type slug.
	 * @return string|null Concrete policy value or null to inherit.
	 */
	public static function get_cpt_policy( string $post_type ): ?string {
		$value = get_option( self::OPTION_CPT_POLICY_PREFIX . $post_type, self::POLICY_INHERIT );
		return in_array( $value, self::concrete_policies(), true ) ? $value : null;
	}

	/**
	 * Persist a per-CPT policy override.
	 *
	 * Pass POLICY_INHERIT (or any non-concrete value) to clear the override and
	 * fall back to the global default.
	 *
	 * @param string $post_type WordPress post type slug.
	 * @param string $policy    Policy value.
	 */
	public static function update_cpt_policy( string $post_type, string $policy ): void {
		if ( in_array( $policy, self::concrete_policies(), true ) ) {
			update_option( self::OPTION_CPT_POLICY_PREFIX . $post_type, $policy );
		} else {
			delete_option( self::OPTION_CPT_POLICY_PREFIX . $post_type );
		}
	}

	// -------------------------------------------------------------------------
	// Dropbox credentials — read-only accessors for wp-config.php constants.
	// No write methods: credentials are managed in wp-config.php, not the DB.
	// -------------------------------------------------------------------------

	/** Returns the Dropbox app key from GATEWAY_DROPBOX_APP_KEY, or empty string. */
	public static function get_dropbox_app_key(): string {
		return defined( 'GATEWAY_DROPBOX_APP_KEY' ) ? GATEWAY_DROPBOX_APP_KEY : '';
	}

	/** Returns the Dropbox app secret from GATEWAY_DROPBOX_APP_SECRET, or empty string. */
	public static function get_dropbox_app_secret(): string {
		return defined( 'GATEWAY_DROPBOX_APP_SECRET' ) ? GATEWAY_DROPBOX_APP_SECRET : '';
	}

	/** Returns the Dropbox refresh token from GATEWAY_DROPBOX_REFRESH_TOKEN, or empty string. */
	public static function get_dropbox_refresh_token(): string {
		return defined( 'GATEWAY_DROPBOX_REFRESH_TOKEN' ) ? GATEWAY_DROPBOX_REFRESH_TOKEN : '';
	}

	/** Returns true when all three Dropbox constants are defined and non-empty. */
	public static function dropbox_configured(): bool {
		return self::get_dropbox_app_key() !== ''
			&& self::get_dropbox_app_secret() !== ''
			&& self::get_dropbox_refresh_token() !== '';
	}

	/**
	 * Returns how many months before a person record is anonymized.
	 */
	public static function get_retention_months(): int {
		$value = get_option( self::OPTION_RETENTION_MONTHS, self::DEFAULT_RETENTION_MONTHS );
		$int   = (int) $value;
		return $int > 0 ? $int : self::DEFAULT_RETENTION_MONTHS;
	}
}
