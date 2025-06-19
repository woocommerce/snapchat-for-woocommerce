<?php
/**
 * Server-side Ad Partner Conversion event representing an "Add to Cart" action.
 *
 * @package SnapchatForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Tracking\ConversionEvent;

use SnapchatForWooCommerce\Tracking\EventIdRegistry;

/**
 * Constructs a Conversion request payload for the ADD_CART event type.
 *
 * This class captures minimal cart data for tracking add-to-cart conversions.
 *
 * @since 0.1.0
 */
final class AddToCartEvent implements ConversionEventInterface {

	/**
	 * Product ID being added to the cart.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $product_id;

	/**
	 * Quantity of the product added.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $quantity;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 * @param int $quantity   Quantity added.
	 */
	public function __construct( int $product_id, int $quantity ) {
		$this->product_id = $product_id;
		$this->quantity   = $quantity;
	}

	/**
	 * Builds the raw Conversion payload for the Ad Partner.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> Conversion event payload.
	 */
	public function build_payload(): array {
		return [
			'event_name'        => 'ADD_CART',
			'event_time'        => time(),
			'event_id'          => EventIdRegistry::get_add_to_cart_id( $this->product_id ),
			'action_source'     => 'WEB',
			'event_source_url'  => wc_get_raw_referer(),
			'user_data'         => [],
			'custom_data'       => [
				'contents' => [
					[
						'id'       => (string) $this->product_id,
						'quantity' => (string) $this->quantity,
					],
				],
			],
		];
	}
}
