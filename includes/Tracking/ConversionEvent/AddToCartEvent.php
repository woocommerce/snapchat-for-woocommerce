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
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'ADD_CART';

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
	 * @param array $args Overrideable payload args.
	 *
	 * @return array<string,mixed> Conversion event payload.
	 */
	public function build_payload( array $args = array() ): array {
		$default = array(
			'event_name'       => self::ID,
			'event_time'       => time(),
			'action_source'    => 'WEB',
			'event_source_url' => wc_get_raw_referer(),
			'user_data'        => array(),
			'custom_data'      => array(
				'contents' => array(
					array(
						'id'       => (string) $this->product_id,
						'quantity' => (string) $this->quantity,
					),
				),
			),
		);

		return array_merge( $default, $args );
	}
}
