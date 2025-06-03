<?php
/**
 * Utility class for managing default option keys and values for the Ad Partner integration.
 *
 * This class helps standardize option key naming and defines default values
 * for settings related to the Ad Partner (e.g., ad account ID, pixel configuration).
 *
 * @package SnapchatForWooCommerce\Utils
 */
namespace SnapchatForWooCommerce\Utils;

/**
 * Provides default option values and namespacing support for Ad Partner plugin settings.
 *
 * This utility is designed to centralize and standardize the definition of option keys
 * used throughout the plugin. It allows prefixing all keys to prevent collisions
 * and supports retrieving an associative array of defaults.
 */
class OptionDefaults {
	/**
	 * Optional prefix for option keys.
	 *
	 * @var string
	 */
	private static string $prefix = '';

	/**
	 * Option key for the Ad Partner ad account ID.
	 */
	public const AD_ACCOUNT_ID   = 'ad_account_id';

	/**
	 * Option key for the Ad Partner organization ID.
	 */
	public const ORGANIZATION_ID = 'organization_id';

	/**
	 * Option key for whether the pixel tracking script is enabled.
	 */
	public const PIXEL_ENABLED   = 'ads_pixel_enabled';

	/**
	 * Option key for the actual pixel tracking script content.
	 */
	public const PIXEL_SCRIPT    = 'ads_pixel_script';

	/**
	 * Sets a custom prefix to namespace all option keys.
	 *
	 * This is useful for isolating keys from other plugins or instances.
	 *
	 * @param string $prefix String to use as the prefix (e.g., 'snapchat_').
	 *
	 * @return void
	 */
	public static function set_prefix( string $prefix ): void {
		self::$prefix = rtrim( $prefix, '_' ) . '_';
	}

	/**
	 * Gets the currently set prefix string.
	 *
	 * @return string The option key prefix.
	 */
	public static function get_prefix(): string {
		return self::$prefix;
	}

	/**
	 * Returns a full option key by appending the given suffix to the prefix.
	 *
	 * @param string $suffix Option key suffix (e.g., 'pixel_enabled').
	 *
	 * @return string Fully-qualified option key.
	 */
	public static function key( string $suffix ): string {
		return self::$prefix . $suffix;
	}

	/**
	 * Returns an associative array of default option keys and their default values.
	 *
	 * These defaults are used when initializing or resetting plugin configuration.
	 *
	 * @return array Associative array of default key => value pairs.
	 */
	public static function get_defaults(): array {
		return [
			self::AD_ACCOUNT_ID   => '',
			self::ORGANIZATION_ID => '',
			self::PIXEL_ENABLED   => false,
			self::PIXEL_SCRIPT    => '',
		];
	}
}
