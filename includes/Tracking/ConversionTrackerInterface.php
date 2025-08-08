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

use WC_Cart;

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
 * ---
 * 🎯 Tracking Event Priority Reference
 *
 * | Impact Level | Description                                                | Recommended Delivery Strategy       |
 * |--------------|------------------------------------------------------------|-------------------------------------|
 * | 🔴 Critical  | Missing this breaks ROAS, attribution, and ad optimization | Use Action Scheduler or job queue   |
 * | 🟠 High      | Important for funnels and retargeting                      | Use Action Scheduler where feasible |
 * | 🟡 Medium    | Useful for remarketing and behavior signals                | Use async request (AJAX)            |
 * | 🟢 Low       | Low impact analytics or audience seeding                   | Use async request (AJAX)            |
 * ---
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
	 * To ensure reliable delivery — even if the customer closes the browser or loses connectivity —
	 * this method must enqueue the event for asynchronous processing using a background job system
	 * such as Action Scheduler or a custom job queue.
	 *
	 * @since 0.1.0
	 * @impact 🔴 critical — Required for accurate revenue attribution and ad spend optimization.
	 *
	 * @param int $order WooCommerce order ID to track.
	 * @return void
	 */
	public function track_purchase( int $order ): void;

	/**
	 * Tracks a checkout initiation event via the Ad Partner’s Conversions API.
	 *
	 * This method should be called when a user first reaches the Checkout page from a previous step
	 * in the funnel (e.g., cart or mini-cart). The implementing class is expected to extract contextual
	 * user data (e.g., session ID, IP address, user agent) and any available cart metadata at that point.
	 * A deduplication identifier (e.g., `event_id`) should be included to reconcile this event with a matching
	 * client-side pixel signal, if present.
	 *
	 * ⚠️ This event should **not** be triggered on simple page reloads to avoid inflating analytics.
	 * However, if the user navigates away from the Checkout and later returns (e.g., using back/forward
	 * buttons or repeating the funnel), it **should** be considered a new and unique `start_checkout` event.
	 *
	 * As a high-priority funnel milestone, this event is critical for tracking drop-off rates,
	 * powering retargeting campaigns, and improving checkout conversion strategies.
	 *
	 * To ensure reliable delivery — even if the user exits the session or disconnects mid-checkout —
	 * this event should be enqueued using a background job processor such as Action Scheduler.
	 *
	 * @since 0.1.0
	 * @impact 🟠 high — Key funnel milestone; essential for retargeting and conversion tracking.
	 *
	 * @param WC_Cart $cart     The WooCommerce cart object containing current session data.
	 * @param string  $event_id The unique event ID used for deduplication.
	 * @return void
	 */
	public function track_start_checkout( WC_Cart $cart, string $event_id = '' ): void;

	/**
	 * Tracks a product add-to-cart event via the Ad Partner’s Conversions API.
	 *
	 * This method should be called when a product is added to the cart server-side
	 * (e.g., during AJAX handlers or custom logic). The implementing class is expected
	 * to extract product details (e.g., ID, price, quantity), contextual metadata
	 * (e.g., session, IP, user agent), and deduplication keys (e.g., event ID) to build
	 * a compatible payload for the Ad Partner’s Conversions API.
	 *
	 * To ensure reliable delivery — even if the customer closes the browser or loses connectivity —
	 * this method must enqueue the event for asynchronous processing using a background job system
	 * such as Action Scheduler or a custom job queue.
	 *
	 * @since 0.1.0
	 * @impact 🟠 high — Valuable for retargeting and funnel analytics.
	 *
	 * @param int    $product_id WooCommerce product ID being added.
	 * @param int    $quantity   Quantity of the product added to cart.
	 * @param string $event_id   The unique event ID.
	 * @return void
	 */
	public function track_add_to_cart( int $product_id, int $quantity, string $event_id = '' ): void;

	/**
	 * Tracks a product view (ViewContent) event via the Ad Partner’s Conversions API.
	 *
	 * This method should be called when a user views a single product page. The implementing class
	 * is expected to extract relevant product metadata (e.g., product ID). along with contextual
	 * user data (e.g., IP address, user agent, session ID), and deduplication identifiers such as
	 * an `event_id`. The constructed payload should comply with the Ad Partner’s Conversions API
	 * specification and be dispatched either immediately or queued for asynchronous processing.
	 *
	 * ⚠️ Important: This event is triggered with high frequency on high-traffic sites. Therefore, it
	 * should **not** be enqueued using Action Scheduler or background job queues, as those tables
	 * would grow rapidly and impact performance. Instead, dispatch the event immediately via a
	 * lightweight async mechanism such as `navigator.sendBeacon()` or a fire-and-forget AJAX request.
	 *
	 * 🧠 For high-quality signal generation, avoid firing this event on simple page reloads. Instead,
	 * treat direct URL visits, redirections, and backward/forward browser navigation as meaningful
	 * product views worthy of triggering this event.
	 *
	 * @since 0.1.0
	 * @impact 🟠 high — Valuable for retargeting and funnel analytics.
	 *
	 * @param int    $product_id WooCommerce product ID being viewed.
	 * @param string $event_id   The unique event ID used for deduplication.
	 * @return void
	 */
	public function track_view_content( int $product_id, string $event_id = '' ): void;

	/**
	 * Tracks a page view event via the Ad Partner’s Conversions API.
	 *
	 * This method should be called when a user views a non-product page (e.g., homepage, category page, blog).
	 * The implementing class is expected to gather contextual metadata such as the current URL,
	 * referrer, session ID, user agent, and IP address. A deduplication identifier (e.g., `event_id`)
	 * should be included if possible to match client-side tracking.
	 *
	 * To avoid performance bottlenecks, this event should be dispatched using a lightweight async method
	 * such as an AJAX call. Avoid queuing it in background job systems like Action Scheduler.
	 *
	 * @since 0.1.0
	 * @impact 🟢 low — General analytics or audience seeding.
	 *
	 * @param string $event_id  The unique event ID used for deduplication.
	 * @return void
	 */
	public function track_page_view( string $event_id = '' ): void;

	/**
	 * Sends a previously constructed conversion event payload to the Ad Partner’s Conversions API.
	 *
	 * This method is typically triggered asynchronously by background job processors such as
	 * Action Scheduler. The implementing class is expected to retrieve necessary authentication
	 * credentials (e.g., access token), contextual data (e.g., user identifiers), and construct
	 * the full API request to the appropriate Ad Partner endpoint.
	 *
	 * The payload may represent events such as purchases or add-to-cart actions and should be
	 * formatted according to the Ad Partner’s specification. This method should also handle
	 * response parsing, error handling, and optionally trigger related WordPress hooks for
	 * extensibility.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $event_payload The event payload to transmit, including metadata and user data.
	 * @param array               $args          Additional arguments passed alongside the payload (e.g., order ID).
	 * @return void
	 */
	public function send( array $event_payload, array $args ): void;
}
