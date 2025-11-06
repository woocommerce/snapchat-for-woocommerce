<?php
/**
 * Server-side Ad Partner Conversion event representing a completed purchase.
 *
 * Builds a structured payload using WooCommerce order details
 * to send to the Ad Partner's Conversions API.
 *
 * @package SnapchatForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Tracking\ConversionEvent;

use WC_Order;
use SnapchatForWooCommerce\Tracking\EventIdRegistry;

/**
 * Constructs a Conversion request payload for the PURCHASE event type.
 *
 * Extracts item details, order totals, and identifiers from
 * the WooCommerce order object to track conversions accurately.
 *
 * @since 0.1.0
 */
final class PurchaseEvent extends EventPayloadBase implements ConversionEventInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'PURCHASE';

	/**
	 * WooCommerce order object.
	 *
	 * @since 0.1.0
	 * @var WC_Order|null
	 */
	private $order;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function __construct( int $order_id ) {
		$this->order = wc_get_order( $order_id );
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
		if ( ! $this->order ) {
			return array();
		}

		$contents = array();
		$ids      = array();

		/**
		 * Product from the Order Line Item.
		 *
		 * @var \WC_Order_Item_Product $item Product item.
		 */
		foreach ( $this->order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$contents[] = array(
				'id'         => (string) $product->get_id(),
				'quantity'   => (string) $item->get_quantity(),
				'item_price' => (string) $product->get_price(),
			);

			$ids[] = (string) $product->get_id();
		}

		$base    = parent::build_payload();
		$default = array(
			'event_name'       => self::ID,
			'event_source_url' => $this->order->get_checkout_order_received_url(),
			'event_id'         => EventIdRegistry::get_purchase_id(),
			'user_data'        => array(),
			'custom_data'      => array(
				'content_ids' => array_filter( $ids, fn( $id ) => ! empty( $id ) ),
				'contents'    => $contents,
				'currency'    => $this->order->get_currency(),
				'num_items'   => (string) $this->order->get_item_count(),
				'order_id'    => (string) $this->order->get_id(),
				'value'       => $this->order->get_total(),
			),
		);

		return array_merge( $base, $default, $args );
	}
}
