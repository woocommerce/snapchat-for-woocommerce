<?php
/**
 * Registry for generating and retrieving unique event IDs per tracking type.
 *
 * Used to support event deduplication between Pixel and CAPI by ensuring
 * that both share the same event_id value for each specific product or order.
 *
 * @package SnapchatForWooCommerce\Tracking
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Tracking;

/**
 * Provides unique, consistent event IDs for each product/order event type.
 *
 * IDs are generated per-request and cached in memory. For cart events,
 * IDs can optionally be persisted in cookies or frontend-localized for reuse.
 *
 * @since 0.1.0
 */
final class EventIdRegistry {

	/**
	 * Event IDs for each tracked product's Add to Cart event.
	 *
	 * @var array<int,string>
	 */
	private static array $add_to_cart_ids = array();

	/**
	 * Cached event ID for the current purchase (if generated).
	 *
	 * @var string|null
	 */
	private static ?string $purchase_id = null;

	/**
	 * Returns a unique event ID for the given product's add to cart event.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 * @return string Unique event ID.
	 */
	public static function get_add_to_cart_id( int $product_id ): string {
		if ( ! isset( self::$add_to_cart_ids[ $product_id ] ) ) {
			self::$add_to_cart_ids[ $product_id ] = wp_generate_uuid4();
		}

		return self::$add_to_cart_ids[ $product_id ];
	}

	/**
	 * Returns a unique event ID for the given purchase event.
	 *
	 * Uses the WooCommerce order ID as the key.
	 *
	 * @since 0.1.0
	 *
	 * @return string Unique event ID.
	 */
	public static function get_purchase_id(): string {
		if ( null === self::$purchase_id ) {
			self::$purchase_id = wp_generate_uuid4();
		}
		return self::$purchase_id;
	}
}
