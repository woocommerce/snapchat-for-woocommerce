<?php
/**
 * Interface definition for Ad Partner Conversion API tracking implementations.
 *
 * This interface defines a contract for classes that send server-side tracking events
 * (such as Add to Cart or Purchase) to an Ad Partner’s Conversions API endpoint.
 *
 * These events are commonly used to enable better attribution, reporting, and optimization
 * for ad campaigns that run on platforms like Snapchat, Meta, TikTok, or others.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

/**
 * Interface for sending server-side conversion events to an Ad Partner API.
 *
 * Implementers of this interface are responsible for preparing and dispatching
 * conversion payloads that correspond to WooCommerce user actions — such as
 * product additions to cart or completed purchases.
 *
 * These events are typically sent through an API endpoint provided by the Ad Partner,
 * and may be transmitted immediately or via background job processors such as Action Scheduler.
 *
 * A matching client-side event (e.g. tracking pixel) is often sent in parallel to improve
 * measurement accuracy. Deduplication identifiers (e.g., `event_id`) are used to avoid double attribution.
 *
 * @since 0.1.0
 */
interface ConversionTrackerInterface {

	/**
	 * Tracks a completed purchase event via the Ad Partner’s Conversions API.
	 *
	 * This method should be called when a WooCommerce order is completed or reaches a defined status.
	 * The implementing class is expected to extract relevant order metadata (e.g., total amount, item list, currency),
	 * contextual information (e.g., user agent, IP), and deduplication identifiers (e.g., event ID or transaction ID).
	 * It should construct a payload compatible with the Ad Partner’s API and dispatch it accordingly.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order WooCommerce order ID to track.
	 * @return void
	 */
	public function track_purchase( int $order ): void;

	/**
	 * Tracks a product add-to-cart event via the Ad Partner’s Conversions API.
	 *
	 * This method should be called when a product is added to the cart server-side
	 * (e.g., during AJAX handlers or custom logic). The implementing class is expected
	 * to extract product details (e.g., ID, price, quantity), contextual metadata
	 * (e.g., session, IP, user agent), and deduplication keys (e.g., event ID) to build
	 * a compatible payload for the Ad Partner’s Conversions API.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id WooCommerce product ID being added.
	 * @param int $quantity   Quantity of the product added to cart.
	 * @return void
	 */
	public function track_add_to_cart( int $product_id, int $quantity ): void;
}
