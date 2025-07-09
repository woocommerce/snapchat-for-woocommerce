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
		add_action( 'wp_ajax_nopriv_' . Helper::with_prefix( 'add_to_cart' ), array( $this, 'handle_async_add_to_cart' ) );
		add_action( 'woocommerce_after_add_to_cart_quantity', array( $this, 'render_event_id_field' ) );
		add_action( Helper::with_prefix( 'send_conversion_event' ), array( $this->tracker, 'send' ), 10, 2 );
		add_action( Helper::with_prefix( 'conversion_sent' ), array( $this, 'mark_as_tracked' ), 10, 2 );
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
		return 'yes' === Options::get( OptionDefaults::CONVERSIONS_ENABLED );
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
		/**
		 * Bail early if the Add to Cart event is handled asynchronously.
		 *
		 * @see ConversionTrackingService::handle_single_product_add_to_cart()
		 * for more details on why we bail early.
		 */
		if ( Helper::is_request_async() ) {
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
		/**
		 * We only track synchronous Add to Cart events in this handler.
		 *
		 * WooCommerce provides three ways for adding products to the cart:
		 *   1. Form submission on single product pages – Standard POST to `?add-to-cart=ID`
		 *   2. AJAX-based add to cart – Typically used in archive pages or custom JS
		 *   3. REST API-based add to cart – Used by modern frontends or headless implementations
		 *
		 * All of these methods trigger the `woocommerce_add_to_cart` hook.
		 * However, we must be careful *not* to process every add-to-cart action here blindly,
		 * because this hook is also triggered for asynchronous flows (AJAX/REST) that are handled elsewhere.
		 *
		 * Snapchat's deduplication logic requires the Pixel (client-side) and CAPI (server-side) events
		 * to share a *common* unique `event_id`. Without this shared ID, Snapchat will treat them
		 * as duplicate events and discard one of them.
		 *
		 * For synchronous form submissions (on single product pages), we inject an `event_id` hidden field
		 * via `render_event_id_field()`, and also add inline JavaScript that assigns a freshly generated
		 * `crypto.randomUUID()` into the field. This ensures every click on the Add to Cart button
		 * generates a new unique ID, even though the page reloads afterward.
		 *
		 * For asynchronous flows (AJAX or REST), this logic is different. There is *no* page reload,
		 * so the same field-based injection strategy doesn't apply. In these cases, the frontend JS
		 * is responsible for generating a new UUID and submitting it via an async request
		 * to the `handle_async_add_to_cart()` method.
		 *
		 * To avoid double-tracking, we skip processing async add-to-cart actions here by checking
		 * `Helper::is_request_async()`. These are already handled in their dedicated async flow.
		 */
		if ( Helper::is_request_async() ) {
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

	/**
	 * Marks the given order as tracked to prevent duplicate conversion reporting.
	 *
	 * This should be called after a successful conversion event has been sent
	 * to the Ad Partner. It updates the order meta with a flag to indicate
	 * that the conversion has already been tracked.
	 *
	 * @since 0.1.0
	 *
	 * @param array $event_payload The payload that was sent to the Ad Partner.
	 * @param array $args {
	 *     Additional arguments passed to the tracking action.
	 *
	 *     @type int $order_id The ID of the WooCommerce order to mark as tracked.
	 * }
	 */
	public function mark_as_tracked( $event_payload, $args ) {
		if ( empty( $args['order_id'] ) ) {
			return;
		}

		$order = wc_get_order( $args['order_id'] );

		// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
		$order->update_meta_data( RemoteConversionTracker::ORDER_CONVERSION_TRACKED_META_KEY, 1 );
		$order->save_meta_data();
	}
}
