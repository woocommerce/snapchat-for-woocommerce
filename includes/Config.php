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
	 * Unique slug identifier for the Ad Partner plugin.
	 *
	 * Used for naming purposes such as Action Scheduler groups, admin page slugs,
	 * and internal prefixing of action/filter hooks.
	 * Example: `snapchat_for_woocommerce`
	 *
	 * @since 0.1.0
	 */
	const PLUGIN_SLUG = 'snapchat_for_woocommerce';

	/**
	 * The namespace used for all REST API endpoints exposed by this plugin.
	 *
	 * This value is appended to `/wp-json/` to form the full route base.
	 * Example: `/wp-json/wc/sfw/snapchat/config`
	 *
	 * @since 0.1.0
	 *
	 * @todo: Change this to the actual WCS endpoint.
	 */
	const REST_NAMESPACE = 'wc/sfw';

	/**
	 * Prefix used for all WordPress storage keys managed by this plugin.
	 *
	 * This prefix is prepended to all storage names (options or transients) to group
	 * them under a consistent namespace.
	 * Example: `snapchat_pixel_enabled`, `snapchat_access_token`
	 *
	 * @since 0.1.0
	 */
	const STORE_PREFIX = 'snapchat_';

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
	 * JavaScript global variable prefix used for frontend data localization.
	 *
	 * This constant defines the prefix applied to all global variables created via `AssetLoader::localize_script()`.
	 * For example, when localizing with object name `TrackingData`, the resulting JS global will be:
	 * `window.snapchatAdsTrackingData`.
	 *
	 * This is used by Ad Partner scripts to access configuration, product metadata, or event identifiers
	 * injected into the page from the server.
	 *
	 * @since 0.1.0
	 *
	 * @see \SnapchatForWooCommerce\Utils\AssetLoader::localize_script()
	 */
	const AD_PARTNER_JS_VAR_PREFIX = 'snapchatAds';
}
