<?php
/**
 * Service class for injecting Ad Partner's Global Site Tag tracking in WooCommerce.
 *
 * This class integrates with WooCommerce product and order workflows to collect
 * product metadata and inject Ad Partner's Pixel tracking events into the frontend
 * via inline JavaScript.
 *
 * It leverages WordPress and WooCommerce hooks to ensure tracking scripts are added
 * only when appropriate (e.g., after product view or order completion) and prevents
 * duplicate purchase tracking through order meta checks.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Config;
use WC_Product;
use function wc_get_price_to_display;
use function wc_get_price_decimals;

/**
 * Handles collection and localization of product/order metadata for the Ad Partner tracking.
 *
 * This service hooks into WooCommerce product rendering, order confirmation,
 * and the site footer to localize product data and emit tracking events via
 * the Ad Partner pixel.
 *
 * Responsibilities include:
 * - Collecting product metadata (e.g., price) from catalog and product pages.
 * - Injecting Ad Partner's tracking events.
 * - Preventing duplicate purchase tracking via a custom order meta key.
 *
 * @since 0.1.0
 */
final class GlobalSiteTag {
	/**
	 * Collected product data for localization.
	 *
	 * @var array
	 */
	protected array $products = array();

	/**
	 * Meta key used to mark orders that have already been tracked.
	 */
	protected const ORDER_CONVERSION_META_KEY = '_snapchat_pixel_tracked';

	/**
	 * Registers WordPress and WooCommerce hooks for product metadata collection
	 * and tracking event injection.
	 *
	 * @since 0.1.0
	 */
	public function register(): void {
		add_filter(
			'woocommerce_loop_add_to_cart_link',
			function ( $link, $product ) {
				if ( $product instanceof WC_Product ) {
					$this->add_product_data( $product );
				}
				return $link;
			},
			10,
			2
		);

		add_action(
			'woocommerce_after_add_to_cart_button',
			function () {
				global $product;

				if ( $product instanceof WC_Product ) {
					$this->add_product_data( $product );
				}
			}
		);

		add_action(
			'woocommerce_after_single_product',
			array( $this, 'track_view_content_event' )
		);

		add_action(
			'woocommerce_before_thankyou',
			array( $this, 'track_purchase_event' )
		);

		add_action(
			'wp_footer',
			array( $this, 'localize_data' )
		);
	}

	/**
	 * Returns the localized data structure to be passed to the frontend via JavaScript.
	 *
	 * Used by `localize_data()` to populate the `Config::AD_PARTNER_JS_GLOBAL` global object.
	 *
	 * @since 0.1.0
	 *
	 * @return array Associative array of currency settings and collected product data.
	 */
	public function get_gtag_data(): array {
		$data = array(
			'currency_minor_unit' => wc_get_price_decimals(),
			'currency'            => get_woocommerce_currency(),
			'products'            => $this->products,
		);

		return $data;
	}

	/**
	 * Collects price data for a WooCommerce product and stores it for localization.
	 *
	 * Called during catalog loop and product page render.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product WooCommerce product object.
	 */
	protected function add_product_data( WC_Product $product ): void {
		$this->products[ $product->get_id() ] = array(
			'price' => wc_get_price_to_display( $product ),
		);
	}

	/**
	 * Outputs a localized JavaScript object (`Config::AD_PARTNER_JS_GLOBAL`) in the page footer.
	 *
	 * Provides price and currency context to client-side scripts.
	 *
	 * @since 0.1.0
	 */
	public function localize_data(): void {
		wp_print_inline_script_tag( sprintf( 'const %1$s = %2$s', Config::AD_PARTNER_JS_GLOBAL, wp_json_encode( $this->get_gtag_data() ) ) );
	}

	/**
	 * Emits the Snapchat `VIEW_CONTENT` tracking event for single product views.
	 *
	 * Hooked into `woocommerce_after_single_product`.
	 *
	 * @since 0.1.0
	 */
	public function track_view_content_event(): void {
		$product_id = get_the_ID();
		$product    = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$this->add_product_data( $product );

		$tracking_data = sprintf(
			'snaptr("track", "VIEW_CONTENT", %s);',
			wp_json_encode(
				array(
					'price'    => wc_get_price_to_display( $product ),
					'currency' => get_woocommerce_currency(),
					'item_ids' => array( $product_id ),
				)
			)
		);

		wp_add_inline_script( Config::ASSET_HANDLE_PREFIX . 'pixel-tracking', $tracking_data );
	}

	/**
	 * Emits the Snapchat `PURCHASE` tracking event after a successful order.
	 *
	 * Hooked into `woocommerce_before_thankyou`. Avoids duplicate firing via meta key.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function track_purchase_event( $order_id ) {
		if ( ! is_order_received_page() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		// Make sure there is a valid order object and it is not already marked as tracked.
		if ( ! $order || 1 === (int) $order->get_meta( self::ORDER_CONVERSION_META_KEY, true ) ) {
			return;
		}

		// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
		$order->update_meta_data( self::ORDER_CONVERSION_META_KEY, 1 );
		$order->save_meta_data();

		$transaction_id  = $order->get_order_number();
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
				$item_ids[]    = $product->get_id();
				$number_items += $item->get_quantity();

				$terms = get_the_terms( $product->get_id(), 'product_cat' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$item_categories[] = $term->name;
					}
				}
			}
		}

		$tracking_data = sprintf(
			'snaptr("track", "PURCHASE", %s);',
			wp_json_encode(
				array(
					'price'          => $total,
					'currency'       => $currency,
					'transaction_id' => $transaction_id,
					'item_ids'       => $item_ids,
					'item_category'  => implode( ', ', array_unique( $item_categories ) ),
					'number_items'   => $number_items,
				)
			)
		);

		wp_add_inline_script( Config::ASSET_HANDLE_PREFIX . 'pixel-tracking', $tracking_data );
	}
}
