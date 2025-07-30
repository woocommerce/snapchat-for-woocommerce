<?php
/**
 * Server-side Ad Partner Conversion event representing user intent
 * to purchase.
 *
 * Builds a structured payload using WooCommerce order details
 * to send to the Ad Partner's Conversions API.
 *
 * @package SnapchatForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Tracking\ConversionEvent;

use WC_Cart;

/**
 * Constructs a Conversion request payload for the START_CHECKOUT event type.
 *
 * This class captures minimal single product page data for tracking
 * start checkout conversions.
 *
 * @since 0.1.0
 */
final class StartCheckoutEvent implements ConversionEventInterface {

	/**
	 * WooCommerce Cart object.
	 *
	 * @since 0.1.0
	 * @var WC_Cart
	 */
	private $cart;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Cart $cart WooCommerce Cart object.
	 */
	public function __construct( WC_Cart $cart ) {
		$this->cart = $cart;
	}

	/**
	 * Builds the raw Conversion payload for the Ad Partner.
	 *
	 * Includes order totals, currency, line items, and metadata.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Overrideable payload args.
	 *
	 * @return array<string,mixed> Conversion event payload.
	 */
	public function build_payload( array $args = array() ): array {
		$contents = array();
		$skus     = array();

		/**
		 * Product from the Order Line Item.
		 *
		 * @var \WC_Order_Item_Product $item Product item.
		 */
		foreach ( $this->cart->get_cart() as $item ) {
			$product = $item['data'];

			if ( ! $product ) {
				continue;
			}

			$contents[] = array(
				'id'         => (string) $product->get_id(),
				'quantity'   => (string) $item->get_quantity(),
				'item_price' => (string) $product->get_price(),
			);

			$skus[] = (string) $product->get_sku();
		}

		$default = array(
			'event_name'       => 'PURCHASE',
			'event_time'       => time(),
			'event_source_url' => wc_get_raw_referer(),
			'action_source'    => 'WEB',
			'user_data'        => array(),
			'custom_data'      => array(
				'content_ids' => array_filter( $skus, fn( $sku ) => ! empty( $sku ) ),
				'contents'    => $contents,
				'currency'    => get_woocommerce_currency(),
				'num_items'   => (string) $this->cart->get_cart_contents_count(),
				'value'       => $this->cart->get_cart_contents_total(),
			),
		);

		return array_merge( $default, $args );
	}
}
