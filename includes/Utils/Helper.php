<?php
/**
 * Utility helper class for common operations in the Snapchat for WooCommerce plugin.
 *
 * @package SnapchatForWooCommerce\Utils
 * @since   0.1.0
 */

namespace SnapchatForWooCommerce\Utils;

use SnapchatForWooCommerce\Config;
use WC_Product;

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
		$store_name = $clean_url . '_' . time();

		if ( $suffix ) {
			$store_name .= '_' . $suffix;
		}

		return 'WooCommerce imported catalog ' . $store_name;
	}

	/**
	 * Get the current epoch timestamp.
	 *
	 * - On 64-bit PHP: returns milliseconds (int).
	 * - On 32-bit PHP: returns seconds (int), since ms won't fit in 32-bit integer.
	 *
	 * This design ensures:
	 * - On 64-bit platforms (most modern servers), you get millisecond-level
	 *   precision.
	 * - On 32-bit platforms, current epoch time in milliseconds would exceed the
	 *   maximum 32-bit signed integer value (~2.1 billion). Since epoch in ms is
	 *   already in the trillions (≈1.7e12), it would overflow. To prevent this,
	 *   we fall back to returning the epoch in seconds, which safely fits within
	 *   a 32-bit int.
	 *
	 * This guarantees the function always returns a safe integer appropriate for
	 * the platform, even if it means sacrificing ms precision on 32-bit PHP.
	 *
	 * @since 0.1.0
	 *
	 * @return int Epoch timestamp (ms on 64-bit, sec on 32-bit).
	 */
	public static function get_event_time() {
		if ( PHP_INT_SIZE >= 8 ) {
			// 64-bit PHP: safe to use milliseconds since epoch.
			return (int) ( microtime( true ) * 1000 );
		}

		// 32-bit PHP: fallback to seconds to avoid integer overflow.
		return time();
	}

	/**
	 * Recursively replaces double quotes with single quotes in strings within an array or object.
	 *
	 * Sometimes Reddit API responses contain JSON strings that contain encoded double quotes,
	 * that breaks WooCommerce logger, resulting to uglified output instead of pretty print.
	 * This function helps sanitize such data before logging.
	 *
	 * @since 0.1.0
	 *
	 * @param array $data Response array that is recursively processed.
	 * @return array Sanitized array with double quotes replaced by single quotes.
	 */
	public static function deep_replace_double_quotes( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::deep_replace_double_quotes( $value );
			}

			return $data;
		}

		if ( is_object( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data->$key = self::deep_replace_double_quotes( $value );
			}

			return $data;
		}

		if ( is_string( $data ) ) {
			// Replace all double quotes with single quotes.
			return str_replace( '"', "'", $data );
		}

		// Return scalars (int, float, bool, null) as-is.
		return $data;
	}

	/**
	 * Checks if the legacy Snapchat plugin is active.
	 *
	 * @return bool True if the legacy Snapchat plugin is active, false otherwise.
	 */
	public static function is_legacy_snapchat_plugin_active() {
		return is_plugin_active( 'snap-pixel-for-woocommerce/snapchat-pixel-for-woocommerce.php' ) || class_exists( 'snap_pixel' );
	}
}
