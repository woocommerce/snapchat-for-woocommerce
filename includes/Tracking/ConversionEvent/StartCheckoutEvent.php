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
use WC_Product;

/**
 * Constructs a Conversion request payload for the START_CHECKOUT event type.
 *
 * This class captures minimal single product page data for tracking
 * start checkout conversions.
 *
 * @since 0.1.0
 */
final class StartCheckoutEvent extends EventPayloadBase implements ConversionEventInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'START_CHECKOUT';

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
		$ids      = array();

		foreach ( $this->cart->get_cart() as $item ) {
			/**
			 * WooCommerce product object.
			 *
			 * @var WC_Product $product Product object.
			 */
			$product = $item['data'];

			if ( ! $product ) {
				continue;
			}

			$contents[] = array(
				'id'         => (string) $product->get_id(),
				'quantity'   => (string) $item['quantity'],
				'item_price' => (string) $product->get_price(),
			);

			$ids[] = (string) $product->get_id();
		}

		$base    = parent::build_payload();
		$default = array(
			'event_name'  => self::ID,
			'user_data'   => array(),
			'custom_data' => array(
				'content_ids' => array_filter( $ids, fn( $id ) => ! empty( $id ) ),
				'contents'    => $contents,
				'currency'    => get_woocommerce_currency(),
				'num_items'   => (string) $this->cart->get_cart_contents_count(),
				'value'       => wc_format_decimal( $this->cart->total ),
			),
		);

		return array_merge( $base, $default, $args );
	}
}
