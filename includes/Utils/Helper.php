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

	/**
	 * Check if the current request is asynchronous.
	 *
	 * Determines whether the current request is being made via AJAX or through a
	 * REST API endpoint — both are considered asynchronous in the context of tracking
	 * and background processing.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if the request is asynchronous (AJAX or REST), false otherwise.
	 */
	public static function is_request_async() {
		return ( wp_doing_ajax() || wp_is_serving_rest_request() );
	}

	/**
	 * Register an AJAX action for both logged-in and non-logged-in users (frontend).
	 *
	 * @since 0.1.1
	 *
	 * @param string   $action    Action name (will be prefixed automatically).
	 * @param callable $callback  Callback function to handle the AJAX request.
	 */
	public static function register_ajax_action( string $action, callable $callback ): void {
		$prefixed_action = self::with_prefix( $action );

		add_action( 'wp_ajax_' . $prefixed_action, $callback );
		add_action( 'wp_ajax_nopriv_' . $prefixed_action, $callback );
	}
}
