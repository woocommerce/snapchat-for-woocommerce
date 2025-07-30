<?php
/**
 * Implements the ConversionTrackerInterface to send data via the Ad Partner's Conversions API.
 *
 * This class builds WooCommerce event payloads and dispatches them via WCS (WooCommerce Connect Server)
 * to the Ad Partner's server-side tracking endpoint.
 *
 * Events such as Add to Cart and Purchase are tracked using Action Scheduler for asynchronous delivery.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Tracking\ConversionEvent\PurchaseEvent;
use SnapchatForWooCommerce\Tracking\ConversionEvent\StartCheckoutEvent;
use SnapchatForWooCommerce\Tracking\ConversionEvent\AddToCartEvent;
use SnapchatForWooCommerce\Tracking\ConversionEvent\ViewContentEvent;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\UserIdentifier;
use SnapchatForWooCommerce\Tracking\Consent;
use WC_Cart;

/**
 * Handles conversion tracking by sending server-side events to the Ad Partner Conversions API.
 *
 * This class is the concrete implementation of {@see ConversionTrackerInterface} responsible
 * for building and queuing tracking events (such as purchase and add-to-cart) and delivering them
 * to the Ad Partner's API through the WCS proxy endpoint.
 *
 * Events are queued asynchronously using Action Scheduler to avoid slowing down frontend or checkout flows.
 *
 * @since 0.1.0
 */
class RemoteConversionTracker implements ConversionTrackerInterface {

	/**
	 * Meta key used to mark orders that have already been tracked.
	 */
	public const ORDER_CONVERSION_TRACKED_META_KEY = '_snapchat_conversion_tracked';

	/**
	 * WCS client used to proxy API requests to the Ad Partner.
	 *
	 * @var WcsClient
	 */
	protected WcsClient $client;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param WcsClient $client WCS API proxy client.
	 */
	public function __construct( WcsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Tracks a WooCommerce purchase event.
	 *
	 * Instantiates a {@see PurchaseEvent} object using the given order ID,
	 * generates a valid API payload, and schedules it for dispatch to the Ad Partner
	 * via Action Scheduler. The payload includes product, revenue, user, and deduplication data.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function track_purchase( int $order_id ): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		// Make sure there is a valid order object and it is not already marked as tracked.
		if ( ! $order || 1 === (int) $order->get_meta( self::ORDER_CONVERSION_TRACKED_META_KEY, true ) ) {
			return;
		}

		$event   = new PurchaseEvent( $order_id );
		$payload = $event->build_payload(
			array(
				'user_data' => UserIdentifier::get_user_data(),
			)
		);
		$args    = array( 'order_id' => $order_id );

		as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array(
				'event_payload' => $payload,
				'args'          => $args,
			),
			Config::PLUGIN_SLUG
		);
	}

	/**
	 * Tracks a WooCommerce checkout initiation event.
	 *
	 * This method should be called when a user first reaches the Checkout page
	 * (i.e., transitions from Cart or Mini-Cart to Checkout). It instantiates a
	 * {@see StartCheckoutEvent} object using the active cart, generates a structured
	 * conversion payload, and schedules it for asynchronous dispatch to the Ad Partner
	 * using Action Scheduler.
	 *
	 * ⚠️ This event should not be triggered on simple page reloads. However, if a user
	 * navigates away and later returns to the Checkout page, it should be treated as a
	 * new `start_checkout` event.
	 *
	 * The payload includes cart contents, currency, value, and contextual identifiers
	 * such as IP and user agent to support deduplication and targeting.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Cart $cart     WooCommerce cart object representing the current session.
	 * @param string  $event_id The unique event ID used for deduplication (optional).
	 * @return void
	 */
	public function track_start_checkout( WC_Cart $cart, string $event_id = '' ): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$event   = new StartCheckoutEvent( $cart );
		$payload = $event->build_payload(
			array(
				'user_data' => UserIdentifier::get_user_data(),
			)
		);

		as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array(
				'event_payload' => $payload,
				'args'          => array(),
			),
			Config::PLUGIN_SLUG
		);

	}
	/**
	 * Tracks a WooCommerce add-to-cart event.
	 *
	 * Instantiates an {@see AddToCartEvent} using the given product ID and quantity,
	 * builds a conversion payload, and schedules it for asynchronous dispatch.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $product_id WooCommerce product ID.
	 * @param int    $quantity   Quantity added to cart.
	 * @param string $event_id The unique event ID.
	 *
	 * @return void
	 */
	public function track_add_to_cart( int $product_id, int $quantity, string $event_id = '' ): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$event   = new AddToCartEvent( $product_id, $quantity );
		$payload = $event->build_payload(
			array(
				'event_id'  => $event_id,
				'user_data' => UserIdentifier::get_user_data(),
			)
		);

		as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array(
				'event_payload' => $payload,
				'args'          => array(),
			),
			Config::PLUGIN_SLUG
		);
	}

	/**
	 * Tracks a WooCommerce view content event.
	 *
	 * This method is triggered by a REST API endpoint when a product detail page is viewed.
	 * It extracts the product ID and event ID from the request body, builds a ViewContentEvent
	 * payload, and schedules it for asynchronous dispatch to the Ad Partner via Action Scheduler.
	 *
	 * The payload includes basic product information, deduplication ID, and user identifiers
	 * for improved event matching and attribution.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $product_id WooCommerce product ID being viewed.
	 * @param string $event_id   The unique event ID used for deduplication.
	 *
	 * @return void
	 */
	public function track_view_content( int $product_id, string $event_id = '' ): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$event   = new ViewContentEvent( $product_id );
		$payload = $event->build_payload(
			array(
				'event_id'  => $body->eventId ?? '', // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'user_data' => UserIdentifier::get_user_data(),
			)
		);

		$this->send( $payload );
	}

	/**
	 * Sends a previously built payload to the Ad Partner Conversions API via WCS.
	 *
	 * This method is intended to be triggered asynchronously by Action Scheduler
	 * using the `send_conversion_event` hook. It retrieves the required pixel ID and
	 * access token from plugin options, adds user-level metadata (e.g. IP and user agent),
	 * and sends the payload to the Conversions API through the WCS proxy.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $event_payload Single event payload.
	 * @param array               $args          Additional args.
	 * @return void
	 */
	public function send( array $event_payload, array $args = array() ): void {
		$token    = Options::get( OptionDefaults::CONVERSION_ACCESS_TOKEN );
		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		if ( ! $token || ! $pixel_id ) {
			return;
		}

		$query   = http_build_query( array( 'access_token' => $token ) );
		$path    = "/conversions/v3/{$pixel_id}/events?{$query}";
		$payload = array( 'data' => array( $event_payload ) );

		if ( defined( 'SNAPCHAT_FOR_WOOCOMMERCE_DEBUG' ) && SNAPCHAT_FOR_WOOCOMMERCE_DEBUG ) {
			wc_get_logger()->debug(
				'Snapchat for WooCommerce: Conversion Payload',
				array(
					'context' => 'tracking',
					'payload' => $payload,
					'args'    => $args,
				)
			);
		}

		$this->client->proxy_post( $path, $payload, false );

		/**
		 * Fires after a conversion event has been sent to the Ad Partner.
		 *
		 * This hook allows other plugins or custom code to perform actions after the conversion payload
		 * has been dispatched, such as logging, triggering additional integrations, or updating metadata.
		 *
		 * @since 0.1.0
		 *
		 * @param array $event_payload The payload that was sent to the Ad Partner.
		 * @param array $args          Additional args.
		 */
		do_action( Helper::with_prefix( 'conversion_sent' ), $event_payload, $args );
	}
}
