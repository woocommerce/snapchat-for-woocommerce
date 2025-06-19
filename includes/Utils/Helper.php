<?php
/**
 * Utility helper class for common operations in the Snapchat for WooCommerce plugin.
 *
 * @package SnapchatForWooCommerce\Utils
 * @since   0.1.0
 */

namespace SnapchatForWooCommerce\Utils;

use SnapchatForWooCommerce\Config;

/**
 * Class Helper
 *
 * Provides utility methods used across the plugin.
 *
 * @since 0.1.0
 */
class Helper {

	/**
	 * Returns a plugin-prefixed identifier string.
	 *
	 * This helps standardize internal action/filter names or option keys
	 * to avoid conflicts with other plugins or themes.
	 *
	 * Example usage:
	 * Helper::with_prefix( 'send_conversion_event' );
	 * // Returns: 'snapchat_for_woocommerce_send_conversion_event'
	 *
	 * @since 0.1.0
	 *
	 * @param string $suffix Identifier to append to the plugin slug.
	 * @return string Fully qualified identifier with plugin prefix.
	 */
	public static function with_prefix( string $suffix ): string {
		return Config::PLUGIN_SLUG . '_' . ltrim( $suffix, '_' );
	}
}
