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
	 * @since 0.1.0
	 *
	 * @param string   $action    Action name (will be prefixed automatically).
	 * @param callable $callback  Callback function to handle the AJAX request.
	 */
	public static function register_ajax_action( string $action, callable $callback ): void {
		$prefixed_action = self::with_prefix( $action );

		add_action( 'wp_ajax_' . $prefixed_action, $callback );
		add_action( 'wp_ajax_nopriv_' . $prefixed_action, $callback );
	}

	/**
	 * Returns true if the plugin debugging mode is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if debugging is enabled, false otherwise.
	 */
	public static function is_logging_enabled(): bool {
		return defined( 'SNAPCHAT_FOR_WOOCOMMERCE_DEBUG' ) && SNAPCHAT_FOR_WOOCOMMERCE_DEBUG;
	}

	/**
	 * Formats a timestamp into a human-readable date and time string.
	 *
	 * Uses the site's date and time format settings to display the timestamp
	 * in a localized format.
	 *
	 * @since 0.1.0
	 *
	 * @param int $timestamp Unix timestamp to format.
	 * @return string Formatted date and time string, or empty if no timestamp is provided.
	 */
	public static function get_formatted_timestamp( $timestamp = 0 ): string {
		if ( ! $timestamp ) {
			return '';
		}

		return date_i18n(
			get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ),
			(int) $timestamp
		);
	}

	/**
	 * Checks if the site has products.
	 *
	 * This method checks if there are any published products in the WooCommerce store.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if there are published products, false otherwise.
	 */
	public static function has_products(): bool {
		$product = wc_get_products(
			array(
				'limit'  => 1,
				'status' => 'publish',
			)
		);

		return count( $product ) > 0;
	}

	/**
	 * Generates a unique store name based on the site's home URL and the current timestamp.
	 *
	 * This function removes the protocol (http:// or https://) from the home URL
	 * and appends the current Unix timestamp to ensure uniqueness.
	 *
	 * @param string $suffix Suffix to be appended to the store name.
	 *
	 * @return string A string composed of the cleaned home URL and the current timestamp.
	 */
	public static function get_store_name( string $suffix = '' ): string {
		$home_url   = get_home_url();
		$clean_url  = preg_replace( '#^https?://#', '', $home_url );
		$store_name = $clean_url . '_woocommerce_' . time();

		if ( $suffix ) {
			$store_name .= '_' . $suffix;
		}

		return $store_name;
	}
}
