<?php
/**
 * Defines a centralized list of service identifiers for the Ad Partner plugin.
 *
 * This class acts as an enum-like container for service keys used throughout the plugin,
 * especially within the ServiceContainer. By referencing constants instead of string literals,
 * it improves type safety, readability, and maintainability.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

/**
 * Enum-like container for service identifiers.
 *
 * These constants represent the valid service keys that can be requested from the ServiceContainer.
 * Using constants avoids typos and ensures consistent service resolution throughout the plugin.
 *
 * This approach also provides a smoother upgrade path to PHP 8.1 native enums in the future.
 *
 * @since 0.1.0
 */
final class ServiceKey {

	/**
	 * Identifier for the Jetpack authenticator service.
	 *
	 * @since 0.1.0
	 */
	public const JETPACK_AUTHENTICATOR = 'jetpack_authenticator';

	/**
	 * Identifier for the WCS API client service.
	 *
	 * @since 0.1.0
	 */
	public const WCS_CLIENT = 'wcs_client';

	/**
	 * Identifier for the Global Site Tag service.
	 *
	 * @since 0.1.0
	 */
	public const GLOBAL_SITE_TAG = 'global_site_tag';

	/**
	 * Identifier for the pixel tracking service.
	 *
	 * @since 0.1.0
	 */
	public const PIXEL_TRACKING = 'pixel_tracking';

	/**
	 * Identifier for the conversion tracking service.
	 *
	 * @since 0.1.0
	 */
	public const CONVERSION_TRACKING = 'conversion_tracking';

	/**
	 * Identifier for the Admin related services and features.
	 *
	 * @since 0.1.0
	 */
	public const ADMIN_SETUP            = 'admin_setup';
	public const PRODUCT_EXPORT_SERVICE = 'product_export_service';

	/**
	 * Identifier for the Settings REST Controller Setup
	 *
	 * @since 0.1.0
	 */
	public const SETTINGS_REST_CONTROLLER_SETUP = 'settings_rest_controller_setup';
}
