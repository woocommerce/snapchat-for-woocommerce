<?php
/**
 * Utility helper class for common operations in the Snapchat for WooCommerce plugin.
 *
 * @package SnapchatForWooCommerce\Utils
 * @since   0.1.0
 */

namespace SnapchatForWooCommerce\Utils;

use Automattic\WooCommerce\Internal\Utilities\Users as WC_Internal_Users;
use SnapchatForWooCommerce\Config;
use WC_Order;
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
	 * Returns the order of the current `order-received` request, but only when the visitor is
	 * allowed to see it.
	 *
	 * WooCommerce renders a generic confirmation page — without any order details — when the
	 * request does not carry the order key, and it also keeps orders that belong to a registered
	 * customer hidden from everybody but that customer. Tracking code must apply the same checks
	 * before loading an order from the `order-received` endpoint, otherwise order and customer
	 * data would be exposed to anyone able to guess an order ID.
	 *
	 * @see \WC_Shortcode_Checkout::order_received()
	 * @see \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\AbstractOrderConfirmationBlock::get_view_order_permissions()
	 * @see self::order_requires_email_verification()
	 *
	 * @since 1.0.4
	 *
	 * @return WC_Order|null The order when the request is authorized to view it, null otherwise.
	 */
	public static function get_verified_order_received_order(): ?WC_Order {
		if ( ! is_order_received_page() ) {
			return null;
		}

		$order_id = absint( get_query_var( 'order-received' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check of the order key shared with the customer in the confirmation URL.
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		/**
		 * WooCommerce filter used to remap the order ID read from the confirmation URL, e.g. by a
		 * payment gateway that redirects with its own reference. Honoured here so tracking follows
		 * whatever order WooCommerce itself resolves and grants access to.
		 *
		 * @see \WC_Shortcode_Checkout::order_received()
		 *
		 * @param int $order_id Order ID read from the `order-received` query var.
		 */
		$order_id = absint( apply_filters( 'woocommerce_thankyou_order_id', $order_id ) );

		/**
		 * WooCommerce filter used to remap the order key read from the confirmation URL. Honoured
		 * for the same reason as `woocommerce_thankyou_order_id` above.
		 *
		 * @see \WC_Shortcode_Checkout::order_received()
		 *
		 * @param string $order_key Order key read from the `key` query argument.
		 */
		$order_key = (string) apply_filters( 'woocommerce_thankyou_order_key', $order_key );

		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );

		// Refunds and other order types are not confirmation pages, hence the `WC_Order` check.
		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		// A missing, malformed (arrays are sanitized to an empty string) or wrong key means WooCommerce did not grant access.
		if ( '' === $order_key || ! $order->key_is_valid( $order_key ) ) {
			return null;
		}

		/**
		 * WooCommerce filter indicating if known (non-guest) shoppers need to be logged in
		 * before they are given access to the order received page. It is read here so that
		 * tracking follows whatever WooCommerce decides to render.
		 *
		 * @see \WC_Shortcode_Checkout::order_received()
		 *
		 * @param bool $verify_known_shoppers If verification is required.
		 */
		$verify_known_shoppers = apply_filters( 'woocommerce_order_received_verify_known_shoppers', true );
		$order_customer_id     = $order->get_customer_id();

		if ( $verify_known_shoppers && $order_customer_id && get_current_user_id() !== $order_customer_id ) {
			return null;
		}

		if ( self::order_requires_email_verification( $order_id ) ) {
			return null;
		}

		return $order;
	}

	/**
	 * Returns true when WooCommerce would ask the visitor to verify their email address before
	 * showing order details, i.e. when it would render `checkout/form-verify-email.php` in place
	 * of the order. Mirrors `WC_Shortcode_Checkout::guest_should_verify_email()`, including the
	 * nonce-checked, POSTed email from a submitted verification form.
	 *
	 * `Users::should_user_verify_order_email()` already returns false once the order has no
	 * billing email, or once the current user is logged in as its owner, so this check is safe to
	 * run unconditionally rather than only for guest orders.
	 *
	 * @see \WC_Shortcode_Checkout::guest_should_verify_email()
	 * @see \Automattic\WooCommerce\Internal\Utilities\Users::should_user_verify_order_email()
	 *
	 * @since 1.0.4
	 *
	 * @param int $order_id Order to check.
	 * @return bool True when the visitor must verify their email before WooCommerce shows the order.
	 */
	private static function order_requires_email_verification( int $order_id ): bool {
		if ( ! class_exists( WC_Internal_Users::class ) ) {
			// Fail closed: without WooCommerce's own check available, assume verification is required.
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified below via wp_verify_nonce(), same as WC_Shortcode_Checkout::guest_should_verify_email().
		$nonce          = isset( $_POST['check_submission'] ) ? sanitize_text_field( wp_unslash( $_POST['check_submission'] ) ) : '';
		$supplied_email = null;

		if ( $nonce && wp_verify_nonce( $nonce, 'wc_verify_email' ) && isset( $_POST['email'] ) ) {
			$supplied_email = sanitize_email( wp_unslash( $_POST['email'] ) );
		}

		return (bool) WC_Internal_Users::should_user_verify_order_email( $order_id, $supplied_email, 'order-received' );
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

	/**
	 * Returns the integration identifier to send in Pixel and CAPI event payload.
	 *
	 * @return string The integration identifier (eg: `woocommerce-v1-0-0`).
	 */
	public static function get_integration_identifier(): string {
		return 'woocommerce-v' . str_replace( '.', '-', SNAPCHAT_FOR_WOOCOMMERCE_VERSION );
	}
}
