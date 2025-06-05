<?php
/**
 * Plugin-wide configuration constants for the Ad Partner integration with WooCommerce.
 *
 * This class centralizes shared configuration values such as REST API namespaces,
 * option prefixes, and script handles, ensuring consistency across the plugin.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

/**
 * Defines global constants used throughout the Ad Partner integration plugin.
 *
 * This final class acts as a container for static configuration constants.
 * It is not intended to be instantiated or extended.
 *
 * @since 0.1.0
 */
final class Config {
	/**
	 * The namespace used for all REST API endpoints exposed by this plugin.
	 *
	 * This value is appended to `/wp-json/` to form the full route base.
	 * Example: `/wp-json/snapchat-comms/v1/track`
	 *
	 * @since 0.1.0
	 */
	const REST_NAMESPACE = 'snapchat-comms/v1';

	/**
	 * Prefix used for all WordPress option keys managed by this plugin.
	 *
	 * This prefix is prepended to all option names to group them under a consistent namespace.
	 * Example: `snapchat_pixel_enabled`, `snapchat_access_token`
	 *
	 * @since 0.1.0
	 */
	const OPTION_PREFIX = 'snapchat_';

	/**
	 * Prefix used for all asset handles registered or enqueued by this plugin.
	 *
	 * Used in `wp_enqueue_script`, `wp_register_style`, etc., to avoid name collisions.
	 * Example: `snapchat_pixel-tracking`, `snapchat_admin-settings`
	 *
	 * @since 0.1.0
	 */
	const ASSET_HANDLE_PREFIX = 'snapchat_';

	/**
	 * Global JavaScript variable name used to localize frontend data for Ad Partner scripts.
	 *
	 * This global (e.g., `window.snapchatAdsData`) is populated with tracking configuration and product data.
	 * Used by frontend scripts to transmit tracking events to the Ad Partner.
	 *
	 * @since 0.1.0
	 */
	const AD_PARTNER_JS_GLOBAL = 'snapchatAdsData';
}
