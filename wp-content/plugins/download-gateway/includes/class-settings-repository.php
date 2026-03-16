<?php
/**
 * SettingsRepository — typed read access to gateway wp_options.
 *
 * All options are stored under the `gateway_` prefix. Defaults are defined
 * here so callers never receive null — add a getter and a default constant
 * for every new setting added in future sub-phases.
 *
 * Writing settings is handled by the Settings_Page form (sub-phase 2b+);
 * this class is read-only.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class SettingsRepository {

	// Option keys.
	const OPTION_GLOBAL_GATE_POLICY = 'gateway_global_gate_policy';
	const OPTION_RETENTION_MONTHS   = 'gateway_retention_months';

	// Valid gate policy values.
	const POLICY_NONE = 'none';
	const POLICY_SOFT = 'soft';
	const POLICY_HARD = 'hard';

	// Defaults.
	const DEFAULT_GLOBAL_GATE_POLICY = self::POLICY_NONE;
	const DEFAULT_RETENTION_MONTHS   = 24;

	/**
	 * Returns the site-wide gate policy: 'none', 'soft', or 'hard'.
	 * Overridden per-resource or per-taxonomy by PolicyResolver.
	 */
	public static function get_global_gate_policy(): string {
		$value = get_option( self::OPTION_GLOBAL_GATE_POLICY, self::DEFAULT_GLOBAL_GATE_POLICY );
		return in_array( $value, array( self::POLICY_NONE, self::POLICY_SOFT, self::POLICY_HARD ), true )
			? $value
			: self::DEFAULT_GLOBAL_GATE_POLICY;
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
