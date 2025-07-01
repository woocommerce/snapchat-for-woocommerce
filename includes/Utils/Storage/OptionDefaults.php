<?php
/**
 * Defines default values and canonical keys for Ad Partner plugin options.
 *
 * This class acts as a centralized registry for all WordPress option keys
 * used by the Ad Partner integration, along with their default values.
 * It ensures consistent key usage and safe fallbacks when no user-defined
 * value has been saved to the database.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

/**
 * Central registry of option keys and default values for the Ad Partner plugin.
 *
 * Defines the list of valid WordPress option keys used by the plugin,
 * along with their default values. This class ensures consistent key usage
 * across the codebase and provides safe fallbacks for unset options.
 *
 * Used by {@see Options} to populate defaults and resolve missing values.
 *
 * @since 0.1.0
 */
final class OptionDefaults {
	/**
	 * Option key for storing the status of Jetpack's connection.
	 *
	 * @since 0.1.0
	 */
	public const IS_JETPACK_CONNECTED = 'is_jetpack_connected';

	/**
	 * Option key for storing the status of the acceptance
	 * of WordPress's Terms & Conditions.
	 *
	 * @since 0.1.0
	 */
	public const WP_TOS_ACCEPTED = 'wp_tos_accepted';

	public const CONFIG_ID = 'config_id';

	/**
	 * Option key for the Ad Partner ad account ID.
	 *
	 * @since 0.1.0
	 */
	public const ADS_ACCOUNT_ID = 'ad_account_id';

	/**
	 * Option key for the Snapchat organization ID.
	 *
	 * @since 0.1.0
	 */
	public const ORGANIZATION_ID = 'organization_id';

	/**
	 * Option key for Snapchat organization name.
	 */
	public const ORGANIZATION_NAME = 'organization_name';

	/**
	 * Option key that toggles whether pixel tracking is enabled.
	 *
	 * @since 0.1.0
	 */
	public const PIXEL_ENABLED = 'ads_pixel_enabled';

	/**
	 * Option key for the Ad Partner's Pixel ID.
	 *
	 * @since 0.1.0
	 */
	public const PIXEL_ID = 'pixel_id';

	/**
	 * Option key that toggles whether Conversion tracking is enabled.
	 *
	 * @since 0.1.0
	 */
	public const CONVERSIONS_ENABLED = 'conversion_enabled';

	/**
	 * Option key for the Ad Partner's Conversion Token.
	 *
	 * @since 0.1.0
	 */
	public const CONVERSION_ACCESS_TOKEN = 'conversion_access_token';

	/**
	 * Returns default values for all known Ad Partner options.
	 *
	 * Used by {@see Options} to provide fallbacks when option values
	 * are not yet persisted in the database.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> Map of option keys to their default values.
	 */
	public static function get_all(): array {
		return array(
			self::IS_JETPACK_CONNECTED    => false,
			self::WP_TOS_ACCEPTED         => false,
			self::CONFIG_ID               => '',
			self::ADS_ACCOUNT_ID          => '',
			self::ORGANIZATION_ID         => '',
			self::ORGANIZATION_NAME       => '',
			self::PIXEL_ENABLED           => false,
			self::PIXEL_ID                => '',
			self::CONVERSIONS_ENABLED     => false,
			self::CONVERSION_ACCESS_TOKEN => '',
		);
	}
}
