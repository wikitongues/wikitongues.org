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
	const OPTION_GLOBAL_GATE_POLICY   = 'gateway_global_gate_policy';
	const OPTION_RETENTION_MONTHS     = 'gateway_retention_months';
	const OPTION_GLOBAL_INTAKE_SET    = 'gateway_global_intake_set';
	const OPTION_GLOBAL_INTAKE_ALWAYS = 'gateway_global_intake_always';
	const OPTION_WEBHOOK_ENDPOINT     = 'gateway_webhook_endpoint';

	/** Option key pattern for per-CPT policy. Sprintf with post_type. */
	const OPTION_CPT_POLICY_PREFIX        = 'gateway_cpt_policy_';
	const OPTION_CPT_INTAKE_SET_PREFIX    = 'gateway_cpt_intake_set_';
	const OPTION_CPT_INTAKE_ALWAYS_PREFIX = 'gateway_cpt_intake_always_';

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
	// Intake form settings.
	// -------------------------------------------------------------------------

	/**
	 * Returns the site-wide default intake field set name, or 'none'.
	 */
	public static function get_global_intake_set(): string {
		return (string) get_option( self::OPTION_GLOBAL_INTAKE_SET, 'none' );
	}

	/**
	 * Returns the per-CPT intake field set name, or null if not set (inherit).
	 *
	 * @param string $post_type WordPress post type slug.
	 * @return string|null Set name / 'none', or null to inherit.
	 */
	public static function get_cpt_intake_set( string $post_type ): ?string {
		$value = get_option( self::OPTION_CPT_INTAKE_SET_PREFIX . $post_type, '' );
		return '' !== $value ? (string) $value : null;
	}

	/**
	 * Persist a per-CPT intake set override.
	 * Pass empty string to delete the override and inherit from global.
	 *
	 * @param string $post_type WordPress post type slug.
	 * @param string $set_name  Set name, 'none', or '' to clear.
	 */
	public static function update_cpt_intake_set( string $post_type, string $set_name ): void {
		if ( '' === $set_name ) {
			delete_option( self::OPTION_CPT_INTAKE_SET_PREFIX . $post_type );
		} else {
			update_option( self::OPTION_CPT_INTAKE_SET_PREFIX . $post_type, $set_name );
		}
	}

	/**
	 * Returns the site-wide always-show-on-passthrough flag.
	 */
	public static function get_global_intake_always(): bool {
		return '1' === (string) get_option( self::OPTION_GLOBAL_INTAKE_ALWAYS, '0' );
	}

	/**
	 * Returns the per-CPT always-show flag, or null if not set (inherit).
	 *
	 * @param string $post_type WordPress post type slug.
	 * @return bool|null
	 */
	public static function get_cpt_intake_always( string $post_type ): ?bool {
		$value = get_option( self::OPTION_CPT_INTAKE_ALWAYS_PREFIX . $post_type, '' );
		if ( '' === $value ) {
			return null;
		}
		return '1' === (string) $value;
	}

	/**
	 * Persist a per-CPT always-show override.
	 * Pass null to delete the override and inherit from global.
	 *
	 * @param string    $post_type WordPress post type slug.
	 * @param bool|null $always    True, false, or null to clear.
	 */
	public static function update_cpt_intake_always( string $post_type, ?bool $always ): void {
		if ( null === $always ) {
			delete_option( self::OPTION_CPT_INTAKE_ALWAYS_PREFIX . $post_type );
		} else {
			update_option( self::OPTION_CPT_INTAKE_ALWAYS_PREFIX . $post_type, $always ? '1' : '0' );
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

	// -------------------------------------------------------------------------
	// Webhook endpoint.
	// -------------------------------------------------------------------------

	/**
	 * Returns the configured webhook endpoint URL, or empty string if not set.
	 */
	public static function get_webhook_endpoint(): string {
		return (string) get_option( self::OPTION_WEBHOOK_ENDPOINT, '' );
	}

	/**
	 * Persist the webhook endpoint URL.
	 * Pass empty string to clear the setting.
	 *
	 * @param string $url Webhook endpoint URL.
	 */
	public static function update_webhook_endpoint( string $url ): void {
		if ( '' === $url ) {
			delete_option( self::OPTION_WEBHOOK_ENDPOINT );
		} else {
			update_option( self::OPTION_WEBHOOK_ENDPOINT, $url );
		}
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
