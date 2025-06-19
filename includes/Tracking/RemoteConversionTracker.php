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
use SnapchatForWooCommerce\Tracking\ConversionEvent\AddToCartEvent;
use SnapchatForWooCommerce\Tracking\ConversionEvent\PurchaseEvent;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\UserIdentifier;

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
	protected const ORDER_CONVERSION_TRACKED_META_KEY = '_snapchat_conversion_tracked';

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
		$order = wc_get_order( $order_id );

		// Make sure there is a valid order object and it is not already marked as tracked.
		if ( ! $order || 1 === (int) $order->get_meta( self::ORDER_CONVERSION_TRACKED_META_KEY, true ) ) {
			return;
		}

		// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
		$order->update_meta_data( self::ORDER_CONVERSION_TRACKED_META_KEY, 1 );
		$order->save_meta_data();

		$event   = new PurchaseEvent( $order_id );
		$payload = $event->build_payload();

		as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array( 'event' => $payload ),
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
	 * @param int $product_id WooCommerce product ID.
	 * @param int $quantity   Quantity added to cart.
	 * @return void
	 */
	public function track_add_to_cart( int $product_id, int $quantity ): void {
		$event   = new AddToCartEvent( $product_id, $quantity );
		$payload = $event->build_payload();

		as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array( 'event' => $payload ),
			Config::PLUGIN_SLUG
		);
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
	 * @param array<string,mixed> $event Single event payload.
	 * @return void
	 */
	public function send( array $event ): void {
		$token    = Options::get( OptionDefaults::CONVERSION_ACCESS_TOKEN );
		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		if ( ! $token || ! $pixel_id ) {
			return;
		}

		$event['user_data'] = UserIdentifier::get_user_data();

		$query   = http_build_query( array( 'access_token' => $token ) );
		$path    = "{$pixel_id}/events?{$query}";
		$payload = array( 'data' => array( $event ) );

		$this->client->proxy_post( '', $path, $payload, 'conversions' );
	}
}
