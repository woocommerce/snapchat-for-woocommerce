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

use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Options;
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
class ConversionTrackingService implements ServiceStatusInterface {

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
		add_filter( Helper::with_prefix( 'filter_tracking_data' ), array( $this, 'populate_tracking_data' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'handle_purchase' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'handle_single_product_add_to_cart' ), 10, 3 );
		add_action( 'wp_ajax_' . Helper::with_prefix( 'add_to_cart' ), array( $this, 'handle_async_add_to_cart' ) );
		add_action( 'woocommerce_after_add_to_cart_quantity', array( $this, 'render_event_id_field' ) );
		add_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array( $this->tracker, 'send' )
		);
	}

	/**
	 * Determines whether Snapchat Conversion tracking is currently enabled.
	 *
	 * This checks the persisted plugin option configured via the admin interface or defaults.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if pixel tracking is enabled; false otherwise.
	 */
	public static function is_enabled(): bool {
		return (bool) Options::get( OptionDefaults::CONVERSIONS_ENABLED );
	}

	/**
	 * Filters and adds localized tracking data sent to the frontend.
	 *
	 * Adds:
	 * - `capi_trigger_action`: The AJAX action name for triggering the async Add to Cart handler job.
	 * - `event_id_el_name`: The name attribute for the event ID hidden input field.
	 *
	 * These values are used by frontend JavaScript to properly associate the event ID with the product.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $tracking_data Existing tracking data array.
	 * @return array<string, mixed> Modified tracking data with CAPI enhancements.
	 */
	public function populate_tracking_data( $tracking_data ) {
		$tracking_data['capi_trigger_action'] = Helper::with_prefix( 'add_to_cart' );
		$tracking_data['event_id_el_name']    = Helper::with_prefix( 'event_id' );

		return $tracking_data;
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
	public function handle_add_to_cart_old( string $cart_item_key, int $product_id, int $quantity ): void {
		if ( Helper::is_add_to_cart_async() ) {
			return;
		}

		$this->tracker->track_add_to_cart( $product_id, $quantity );
	}

	/**
	 * Outputs a hidden input field for the Event ID on single product pages.
	 *
	 * This is used to inject a unique UUID per Add to Cart action, enabling
	 * deduplication between Pixel and CAPI events.
	 *
	 * Also injects inline JavaScript that generates a `window.crypto.randomUUID()` and
	 * assigns it to the hidden field.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render_event_id_field(): void {
		$attr = Helper::with_prefix( 'event_id' );

		printf(
			'<input type="hidden" name="%1$s" value="" />',
			esc_attr( $attr ),
		);

		wp_print_inline_script_tag(
			sprintf(
				'document.querySelector("[name=%s]").value = window.crypto.randomUUID()',
				esc_attr( $attr )
			)
		);
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
	public function handle_single_product_add_to_cart( string $cart_item_key, int $product_id, int $quantity ): void {
		if ( Helper::is_add_to_cart_async() ) {
			return;
		}

		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$event_id = sanitize_text_field( wp_unslash( $_POST[ Helper::with_prefix( 'event_id' ) ] ?? '' ) );
		$this->tracker->track_add_to_cart( $product_id, $quantity, $event_id );
	}

	/**
	 * Handles AJAX-based Add to Cart requests for server-side tracking.
	 *
	 * This endpoint is called when a product is added to the cart synchronously
	 * via JavaScript.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function handle_async_add_to_cart(): void {
		check_ajax_referer( 'capi_nonce', 'security' );

		$product_id = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );
		$quantity   = absint( wp_unslash( $_POST['quantity'] ?? 0 ) );
		$event_id   = sanitize_text_field( wp_unslash( $_POST['event_id'] ?? '' ) );

		$this->tracker->track_add_to_cart( $product_id, $quantity, $event_id );
	}
}
