<?php
/**
 * Tests for the RemoteConversionTracker class.
 *
 * @package SnapchatForWooCommerce\Tests\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Tracking;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Tracking\RemoteConversionTracker;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Tracking\ConversionEventLogger;

/**
 * @covers \SnapchatForWooCommerce\Tracking\RemoteConversionTracker
 */
class RemoteConversionTrackerTest extends WP_UnitTestCase {

	use ConversionSendTestHelpers;

	/**
	 * WCS client mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject&WcsClient
	 */
	protected $client;

	/**
	 * Class under test.
	 *
	 * @var RemoteConversionTracker
	 */
	protected $tracker;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->client  = $this->createMock( WcsClient::class );
		$this->tracker = new RemoteConversionTracker(
			$this->client,
			new ConversionEventLogger(
				wc_get_logger()
			)
		);
	}

	public function tear_down(): void {
		$hook = Helper::with_prefix( 'send_conversion_event' );
		as_unschedule_all_actions( $hook, null, Config::PLUGIN_SLUG );
		parent::tear_down();
	}

	/**
	 * Test that track_purchase schedules an async action.
	 */
	public function test_track_purchase_dispatches_async_action(): void {
		$order    = wc_create_order();
		$order_id = $order->get_id();

		$this->tracker->track_purchase( $order_id );

		$hook = Helper::with_prefix( 'send_conversion_event' );
		$this->assertNotFalse( as_next_scheduled_action( $hook, null, Config::PLUGIN_SLUG ) );
	}

	/**
	 * Test that reloading the order confirmation page queues only one Purchase send.
	 */
	public function test_track_purchase_queues_once_per_order(): void {
		$order_id = wc_create_order()->get_id();

		$this->tracker->track_purchase( $order_id );
		$this->tracker->track_purchase( $order_id );

		$this->assertCount( 1, $this->pending_sends( $order_id ) );
		$this->assertNotEmpty(
			wc_get_order( $order_id )->get_meta( RemoteConversionTracker::ORDER_CONVERSION_QUEUED_META_KEY )
		);
	}

	/**
	 * Test that an expired reservation no longer blocks queueing the Purchase again.
	 */
	public function test_track_purchase_requeues_after_reservation_expires(): void {
		$order = wc_create_order();
		$order->update_meta_data( RemoteConversionTracker::ORDER_CONVERSION_QUEUED_META_KEY, time() - HOUR_IN_SECONDS );
		$order->save_meta_data();

		$this->tracker->track_purchase( $order->get_id() );

		$this->assertCount( 1, $this->pending_sends( $order->get_id() ) );
	}

	/**
	 * Test that an order tracked before the queued meta existed queues nothing.
	 */
	public function test_track_purchase_skips_tracked_order(): void {
		$order = wc_create_order();
		$order->update_meta_data( RemoteConversionTracker::ORDER_CONVERSION_TRACKED_META_KEY, 1 );
		$order->save_meta_data();

		$this->tracker->track_purchase( $order->get_id() );

		$this->assertCount( 0, $this->pending_sends( $order->get_id() ) );
	}

	/**
	 * Test that the queued meta applies per order, not to every order.
	 */
	public function test_track_purchase_queues_each_order(): void {
		$first  = wc_create_order()->get_id();
		$second = wc_create_order()->get_id();

		$this->tracker->track_purchase( $first );
		$this->tracker->track_purchase( $second );

		$this->assertCount( 1, $this->pending_sends( $first ) );
		$this->assertCount( 1, $this->pending_sends( $second ) );
	}

	/**
	 * Test that an order isn't marked queued when its Purchase send couldn't be queued.
	 */
	public function test_track_purchase_skips_queued_meta_when_queueing_fails(): void {
		$order_id = wc_create_order()->get_id();
		add_filter( 'pre_as_enqueue_async_action', fn() => 0 );

		$this->tracker->track_purchase( $order_id );

		$this->assertEmpty(
			wc_get_order( $order_id )->get_meta( RemoteConversionTracker::ORDER_CONVERSION_QUEUED_META_KEY )
		);
	}

	/**
	 * Test that track_add_to_cart schedules an async action.
	 */
	public function test_track_add_to_cart_dispatches_async_action(): void {
		$this->tracker->track_add_to_cart( 123, 2 );

		$hook = Helper::with_prefix( 'send_conversion_event' );
		$this->assertNotFalse( as_next_scheduled_action( $hook, null, Config::PLUGIN_SLUG ) );
	}

	/**
	 * Test that send does nothing if token or pixel ID missing.
	 */
	public function test_send_returns_early_if_no_token_or_pixel_id(): void {
		Options::set( OptionDefaults::CONVERSION_ACCESS_TOKEN, '' );
		Options::set( OptionDefaults::PIXEL_ID, '' );

		$this->client->expects( $this->never() )->method( 'proxy_post' );

		$this->tracker->send( array( 'event_name' => 'test' ), array() );
	}

	/**
	 * Test send issues API call when token and pixel ID are present.
	 */
	public function test_send_calls_proxy_post(): void {
		Options::set( OptionDefaults::CONVERSION_ACCESS_TOKEN, 'token_abc' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel_456' );

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.55';
		$_SERVER['HTTP_USER_AGENT']      = 'PHPUnitTestRunner';

		$this->client->expects( $this->once() )
			->method( 'proxy_post' )
			->willReturn( new \WP_REST_Response( array(), 200 ) )
			->with(
				$this->callback( fn( $path ) => str_starts_with( $path, '/conversions/v3/pixel_456/events?access_token=token_abc' ) ),
				$this->callback(
					function ( $payload ) {
						return isset( $payload['data'][0]['user_data']['client_ip_address'] )
						&& $payload['data'][0]['user_data']['client_ip_address'] === '203.0.113.55'
						&& $payload['data'][0]['user_data']['client_user_agent'] === 'PHPUnitTestRunner';
					}
				)
			);

		$this->tracker->send(
			array(
				'event_name' => 'Purchase',
				'user_data'  => [
					'client_ip_address' => '203.0.113.55',
					'client_user_agent' => 'PHPUnitTestRunner',
				]
			), array() );
	}

	/**
	 * Test that a 2xx response fires conversion_sent and not conversion_failed.
	 */
	public function test_send_fires_conversion_sent_on_success(): void {
		$this->set_credentials();
		$this->client->method( 'proxy_post' )->willReturn( new \WP_REST_Response( array(), 200 ) );

		$sent   = $this->record_action( 'conversion_sent' );
		$failed = $this->record_action( 'conversion_failed' );

		$payload = array( 'event_name' => 'PURCHASE' );
		$args    = array( 'order_id' => $this->create_order_without_client_data() );
		$this->tracker->send( $payload, $args );

		$this->assertSame( array( array( $payload, $args ) ), $sent->calls );
		$this->assertSame( array(), $failed->calls );
	}

	/**
	 * Test that a failed request fires conversion_failed with the error and not conversion_sent.
	 *
	 * @dataProvider send_error_provider
	 *
	 * @param \WP_Error $error Error returned by the WCS client.
	 */
	public function test_send_fires_conversion_failed_on_error( \WP_Error $error ): void {
		$this->set_credentials();
		$this->client->method( 'proxy_post' )->willReturn( $error );

		$sent   = $this->record_action( 'conversion_sent' );
		$failed = $this->record_action( 'conversion_failed' );

		$payload = array( 'event_name' => 'PURCHASE' );
		$args    = array( 'order_id' => $this->create_order_without_client_data() );
		$this->tracker->send( $payload, $args );

		$this->assertSame( array(), $sent->calls );
		$this->assertSame( array( array( $payload, $args, $error ) ), $failed->calls );
	}

	/**
	 * Errors the WCS client returns for a failed request.
	 *
	 * @return array
	 */
	public function send_error_provider(): array {
		return array(
			'transport error'  => array( $this->transport_error() ),
			'non-2xx response' => array( $this->http_error( 500 ) ),
		);
	}

	/**
	 * Sets a CAPI token and pixel ID so send() reaches the client.
	 */
	private function set_credentials(): void {
		Options::set( OptionDefaults::CONVERSION_ACCESS_TOKEN, 'token_abc' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel_456' );
	}

	/**
	 * Records every call to a prefixed action.
	 *
	 * @param string $suffix Action name without the plugin prefix.
	 * @return object Object whose `calls` property collects each call's arguments.
	 */
	private function record_action( string $suffix ): object {
		$recorder        = new \stdClass();
		$recorder->calls = array();

		add_action(
			Helper::with_prefix( $suffix ),
			function ( ...$args ) use ( $recorder ) {
				$recorder->calls[] = $args;
			},
			10,
			3
		);

		return $recorder;
	}

	/**
	 * Creates an order with no stored IP address or user agent, so send() leaves the payload as is.
	 *
	 * @return int Order ID.
	 */
	private function create_order_without_client_data(): int {
		$order = wc_create_order();
		$order->set_customer_ip_address( '' );
		$order->set_customer_user_agent( '' );
		$order->save();

		return $order->get_id();
	}
}
