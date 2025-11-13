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
	 * Returns a unique event ID for the purchase event.
	 *
	 * @since 0.1.0
	 *
	 * @return string Unique event ID.
	 */
	public static function get_purchase_id(): string {
		static $purchase_event_id = null;
		if ( null === $purchase_event_id ) {
			$purchase_event_id = wp_generate_uuid4();
		}
		return $purchase_event_id;
	}
}
