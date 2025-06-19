<?php
/**
 * Registers and manages WooCommerce hooks for conversion tracking.
 *
 * This class integrates with core WooCommerce events (such as purchases and add-to-cart actions)
 * and delegates conversion tracking responsibilities to an underlying tracker implementation.
 *
 * It acts as a coordinator between WooCommerce events and the Ad Partner's Conversions API.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Utils\Helper;

/**
 * Service class for registering WooCommerce conversion tracking hooks.
 *
 * This class listens to WooCommerce events and triggers corresponding
 * server-side conversion tracking via the provided {@see ConversionTrackerInterface}.
 *
 * - `woocommerce_thankyou` - triggers purchase event tracking
 * - `woocommerce_add_to_cart` - triggers add-to-cart tracking
 * - Custom async action (e.g., Action Scheduler) can be used via `send_conversion_event`
 *
 * @since 0.1.0
 */
class ConversionTrackingService {

	/**
	 * Instance of a class implementing conversion tracking logic.
	 *
	 * @var ConversionTrackerInterface
	 */
	protected ConversionTrackerInterface $tracker;

	/**
	 * Constructor.
	 *
	 * Accepts a concrete implementation of the {@see ConversionTrackerInterface},
	 * which is responsible for sending the actual tracking payloads to the Ad Partner API.
	 *
	 * @since 0.1.0
	 *
	 * @param ConversionTrackerInterface $tracker Tracker implementation instance.
	 */
	public function __construct( ConversionTrackerInterface $tracker ) {
		$this->tracker = $tracker;
	}

	/**
	 * Registers WooCommerce hooks for conversion event tracking.
	 *
	 * It attaches conversion tracking to:
	 * - Purchase events (`woocommerce_thankyou`)
	 * - Add to Cart events (`woocommerce_add_to_cart`)
	 * - Asynchronous tracking hook via Action Scheduler
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_thankyou', array( $this, 'handle_purchase' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'handle_add_to_cart' ), 10, 3 );
		add_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array( $this->tracker, 'send' )
		);
	}

	/**
	 * Callback for WooCommerce purchase completion events.
	 *
	 * Invoked after a customer completes checkout. Delegates to the tracker
	 * to send the order data to the Ad Partner's Conversions API.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function handle_purchase( int $order_id ): void {
		$this->tracker->track_purchase( $order_id );
	}

	/**
	 * Callback for WooCommerce add-to-cart actions.
	 *
	 * Triggered when a product is added to the cart.
	 * Passes product ID and quantity to the tracker for building the tracking payload.
	 *
	 * @since 0.1.0
	 *
	 * @param string $cart_item_key Unique key for the cart item.
	 * @param int    $product_id    WooCommerce product ID added to the cart.
	 * @param int    $quantity      Quantity of product added.
	 * @return void
	 */
	public function handle_add_to_cart( string $cart_item_key, int $product_id, int $quantity ): void {
		$this->tracker->track_add_to_cart( $product_id, $quantity );
	}
}
