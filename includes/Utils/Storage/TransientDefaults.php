<?php
/**
 * Defines default values and TTLs for Ad Partner plugin transients.
 *
 * This class acts as a centralized registry for all WordPress transient keys
 * used by the Ad Partner integration, along with their expiration durations.
 * It ensures consistent key usage and cache lifetimes across the plugin.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

/**
 * Central registry of transient keys and TTLs for the Ad Partner plugin.
 *
 * Defines the list of valid WordPress transient keys used by the plugin,
 * along with their default expiration times (in seconds). This class ensures
 * consistent caching behavior and avoids hardcoding TTLs throughout the codebase.
 *
 * Used by {@see TransientStorage} to control expiration when storing transient values.
 *
 * @since 0.1.0
 */
final class TransientDefaults {

	/**
	 * Transient key for storing the remote pixel tracking script.
	 *
	 * @since 0.1.0
	 */
	public const PIXEL_SCRIPT = 'ads_pixel_script';

	/**
	 * Returns defaults for all known Ad Partner transients.
	 *
	 * Used by {@see TransientStorage} when saving values.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,int> Map of transient keys to their defaults.
	 */
	public static function get_all(): array {
		return array(
			self::PIXEL_SCRIPT => '',
		);
	}

	/**
	 * Returns TTLs for all known Ad Partner transients.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,int> Map of transient keys to TTL in seconds.
	 */
	private static function get_ttls(): array {
		return array(
			self::PIXEL_SCRIPT => MONTH_IN_SECONDS,
		);
	}

	/**
	 * Returns the TTL for a specific transient key, or a default fallback.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The transient key (without prefix).
	 * @return int TTL in seconds.
	 */
	public static function get_ttl( string $key ): int {
		$map = self::get_ttls();
		return $map[ $key ] ?? HOUR_IN_SECONDS;
	}
}
