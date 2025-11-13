<?php
/**
 * Implementation of PixelTracker that retrieves and injects the Snapchat Pixel script remotely.
 *
 * This tracker checks whether pixel tracking is enabled, and if so, either retrieves a cached script
 * or fetches a fresh one from Snapchat via the WCS API. The script is optionally personalized
 * with the logged-in user’s email address for audience matching.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Storage;
use SnapchatForWooCommerce\Tracking\Consent;
use SnapchatForWooCommerce\Utils\UserIdentifier;
use SnapchatForWooCommerce\Config;

/**
 * Fetches and injects Snapchat pixel tracking code into WooCommerce frontend pages.
 *
 * Responsibilities:
 * - Checks plugin option to determine if pixel tracking is enabled.
 * - Retrieves pixel script from local cache or Snapchat Ads API.
 * - Injects sanitized pixel JavaScript into the frontend via `wp_head`.
 * - Optionally personalizes the script using the logged-in user's email.
 *
 * Dependencies:
 * - {@see WcsClient} for making remote API calls to fetch the pixel script.
 * - {@see JetpackAuthenticator} for securely authenticating requests.
 * - {@see OptionsStore} and {@see OptionDefaults} for managing plugin settings and cache.
 *
 * @see \SnapchatForWooCommerce\Tracking\PixelTrackerInterface
 * @since 0.1.0
 */
final class RemotePixelTracker implements PixelTrackerInterface {
	/**
	 * Meta key used to mark orders that have already been tracked.
	 */
	protected const ORDER_PIXEL_TRACKED_META_KEY = '_snapchat_pixel_tracked';

	/**
	 * Client for making authenticated proxy requests to Snapchat Ads API.
	 *
	 * @var WcsClient
	 */
	private WcsClient $wcs_client;

	/**
	 * Constructor.
	 *
	 * @param WcsClient $wcs_client WCS API client.
	 */
	public function __construct( WcsClient $wcs_client ) {
		$this->wcs_client = $wcs_client;
	}

	/**
	 * Injects the Snapchat Pixel script into the footer.
	 * Personalized if possible, and sanitized using `wp_kses`.
	 *
	 * @since 0.1.0
	 */
	public function maybe_inject_pixel(): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$allowed_tags = array(
			'script'   => array(
				'type'  => array(),
				'src'   => array(),
				'async' => array(),
			),
			'#comment' => array(),
		);

		echo wp_kses( $this->get_pixel_script(), $allowed_tags );
	}

	/**
	 * Adds personalization to the pixel script based on the current user.
	 *
	 * If the user is logged in, injects their email address into the script for
	 * more accurate audience tracking. If not logged in, removes placeholder elements.
	 *
	 * @since 0.1.0
	 *
	 * @param string $script Raw pixel JavaScript with placeholders.
	 * @return string Personalized pixel script.
	 */
	protected static function personalize_tracking_script( string $script ): string {
		// Will be implemented client-side.
		$script = str_replace(
			"snaptr('track', 'PAGE_VIEW');",
			'',
			$script
		);

		// @todo: use this once we integrate with Consent API.
		if ( 0 && is_user_logged_in() ) { // for future use.
			$user       = wp_get_current_user();
			$user_email = $user->user_email;

			// Escape the email for JS safety.
			$escaped_email = esc_js( $user_email );

			// Replace the placeholder with actual email.
			return str_replace(
				"'__INSERT_USER_EMAIL__'",
				"'" . $escaped_email . "'",
				$script
			);
		}

		// If user is not logged in, replace with empty string or remove the key.
		return str_replace(
			"'user_email': '__INSERT_USER_EMAIL__'",
			'',
			$script
		);
	}

	/**
	 * Validates that the pixel script includes the expected Ad Partner URL.
	 *
	 * This ensures the cached script hasn't been tampered with.
	 *
	 * @since 0.1.0
	 *
	 * @param string $script The script HTML string.
	 *
	 * @return bool True if valid, false if tampered.
	 */
	private static function is_valid_pixel_script( string $script ): bool {
		return strpos( $script, PixelDefaults::EXPECTED_SCRIPT_URL ) !== false;
	}

	/**
	 * Retrieves the Snapchat Pixel script, either from cache or the remote API.
	 *
	 * If not cached, it authenticates with Jetpack and queries the Snapchat Ads API for pixel script.
	 * The result is cached in the options store and sanitized before being returned.
	 *
	 * @since 0.1.0
	 *
	 * @return string The sanitized pixel script, or empty string on failure.
	 */
	private function get_pixel_script() {
		$pixel_script = Transients::get( TransientDefaults::PIXEL_SCRIPT );

		if ( $pixel_script && self::is_valid_pixel_script( $pixel_script ) ) {
			return self::personalize_tracking_script( $pixel_script );
		}

		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		if ( empty( $pixel_id ) ) {
			return '';
		}

		$path     = sprintf( '/ads/v1/pixels/%s', $pixel_id );
		$response = $this->wcs_client->proxy_get( $path );

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$data = $response->get_data();

		if ( empty( $data ) ) {
			return '';
		}

		$pixel_script = $data['pixels'][0]['pixel']['pixel_javascript'] ?? '';

		if ( ! $pixel_script ) {
			return '';
		}

		Transients::set( TransientDefaults::PIXEL_SCRIPT, $pixel_script );

		return self::personalize_tracking_script( $pixel_script );
	}

	/**
	 * Emits the Snapchat `PURCHASE` tracking event after a successful order.
	 *
	 * Hooked into `woocommerce_before_thankyou`. Avoids duplicate firing via meta key.
	 *
	 * @since 0.1.0
	 */
	public function track_purchase_event() {
		if ( ! is_order_received_page() ) {
			return;
		}

		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$order_id = (int) get_query_var( 'order-received' );
		$order    = wc_get_order( $order_id );

		// Make sure there is a valid order object and it is not already marked as tracked.
		if ( ! $order || 1 === (int) $order->get_meta( self::ORDER_PIXEL_TRACKED_META_KEY, true ) ) {
			return;
		}

		// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
		$order->update_meta_data( self::ORDER_PIXEL_TRACKED_META_KEY, 1 );
		$order->save_meta_data();

		$total           = $order->get_total();
		$currency        = $order->get_currency();
		$item_ids        = array();
		$item_categories = array();
		$number_items    = 0;

		foreach ( $order->get_items() as $item ) {
			/**
			 * Product from the Order Line Item.
			 *
			 * @var \WC_Order_Item_Product $item Product item.
			 */
			$product = $item->get_product();
			if ( $product ) {
				$item_ids[]    = (string) $product->get_id();
				$number_items += $item->get_quantity();

				$terms = get_the_terms( $product->get_id(), 'product_cat' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$item_categories[] = $term->name;
					}
				}
			}
		}

		$event_id = EventIdRegistry::get_purchase_id();
		$payload  = array(
			'price'           => $total,
			'currency'        => $currency,
			'event_id'        => $event_id,
			'client_dedup_id' => $event_id,
			'transaction_id'  => $order_id,
			'item_ids'        => $item_ids,
			'item_category'   => implode( ', ', array_unique( $item_categories ) ),
			'number_items'    => $number_items,
			'integration'     => 'woocommerce-v1',
			'ip_address'      => UserIdentifier::add_ip_address(),
		);

		if ( Storage\Helper::is_collect_pii_enabled() ) {
			UserIdentifier::add_user_details( $payload );
		}

		$tracking_data = sprintf(
			'snaptr("track", "PURCHASE", %s);',
			wp_json_encode( $payload )
		);

		wp_add_inline_script( Config::ASSET_HANDLE_PREFIX . 'tracking', $tracking_data );
	}
}
