<?php
/**
 * End-to-end tests for delivering Purchase conversion events through Action Scheduler.
 *
 * @package SnapchatForWooCommerce\Tests\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Tracking;

use ActionScheduler;
use ActionScheduler_Store;
use WP_Error;
use WP_REST_Response;
use WP_UnitTestCase;
use WC_Product_Simple;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\ServiceContainer;
use SnapchatForWooCommerce\ServiceKey;
use SnapchatForWooCommerce\Tracking\ConversionEventLogger;
use SnapchatForWooCommerce\Tracking\ConversionTrackingService;
use SnapchatForWooCommerce\Tracking\RemoteConversionTracker;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Options;

/**
 * @covers \SnapchatForWooCommerce\Tracking\ConversionTrackingService
 * @covers \SnapchatForWooCommerce\Tracking\RemoteConversionTracker
 */
class PurchaseConversionDeliveryTest extends WP_UnitTestCase {

	/**
	 * WCS client mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject&WcsClient
	 */
	protected $client;

	/**
	 * Tracker wired to the mocked client.
	 *
	 * @var RemoteConversionTracker
	 */
	protected $tracker;

	/**
	 * Set up test environment.
	 *
	 * The plugin's own ConversionTrackingService is already hooked during bootstrap. Its callbacks
	 * are removed so only the service under test, wired to the mocked client, handles the hooks.
	 * WP_UnitTestCase restores every hook after each test.
	 */
	public function set_up(): void {
		parent::set_up();

		$container_service = ServiceContainer::get( ServiceKey::CONVERSION_TRACKING );
		foreach ( array( 'send_conversion_event', 'conversion_sent', 'conversion_failed' ) as $suffix ) {
			remove_all_actions( Helper::with_prefix( $suffix ) );
		}
		remove_action( 'action_scheduler_begin_execute', array( $container_service, 'start_queued_send' ) );
		remove_action( 'action_scheduler_after_execute', array( $container_service, 'end_queued_send' ) );
		remove_action( 'action_scheduler_failed_execution', array( $container_service, 'end_queued_send' ) );

		Options::set( OptionDefaults::CONVERSION_ACCESS_TOKEN, 'token_abc' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel_456' );

		$this->client  = $this->createMock( WcsClient::class );
		$this->tracker = new RemoteConversionTracker( $this->client, new ConversionEventLogger( wc_get_logger() ) );
		( new ConversionTrackingService( $this->tracker ) )->register_hooks();
	}

	/**
	 * Tear down test environment.
	 */
	public function tear_down(): void {
		as_unschedule_all_actions( Helper::with_prefix( 'send_conversion_event' ), null, Config::PLUGIN_SLUG );
		parent::tear_down();
	}

	/**
	 * Test that a failed Purchase ends Failed, stays untracked and isn't resent automatically.
	 *
	 * @dataProvider send_error_provider
	 *
	 * @param WP_Error $error Error returned by the WCS client.
	 */
	public function test_failed_purchase_fails_without_automatic_resend( WP_Error $error ): void {
		$this->client->expects( $this->once() )->method( 'proxy_post' )->willReturn( $error );
		$order_id  = wc_create_order()->get_id();
		$action_id = $this->queue_purchase( $order_id );

		$this->run_action( $action_id );

		$order = wc_get_order( $order_id );
		$this->assertSame( ActionScheduler_Store::STATUS_FAILED, $this->status( $action_id ) );
		$this->assertEmpty( $order->get_meta( RemoteConversionTracker::ORDER_CONVERSION_TRACKED_META_KEY ) );
		$this->assertEmpty( $order->get_meta( RemoteConversionTracker::ORDER_CONVERSION_QUEUED_META_KEY ) );
		$this->assertCount( 0, $this->pending_sends() );
	}

	/**
	 * Errors a Purchase send can fail with.
	 *
	 * @return array
	 */
	public function send_error_provider(): array {
		return array(
			'timeout'          => array( $this->transport_error() ),
			'HTTP 400'         => array( $this->http_error( 400 ) ),
			'HTTP 429'         => array( $this->http_error( 429 ) ),
			'HTTP 503'         => array( $this->http_error( 503 ) ),
			'sandbox block'    => array( new WP_Error( 'snapchat_sandbox_remote_request_blocked', 'Blocked', array( 'status' => 403 ) ) ),
			'no Jetpack token' => array( new WP_Error( 'missing_token' ) ),
		);
	}

	/**
	 * Test that loading the order confirmation page after a failed send queues the Purchase again.
	 */
	public function test_reload_after_failed_send_queues_purchase_again(): void {
		$this->client->method( 'proxy_post' )->willReturnOnConsecutiveCalls(
			$this->transport_error(),
			new WP_REST_Response( array(), 200 )
		);
		$order_id = wc_create_order()->get_id();
		$this->run_action( $this->queue_purchase( $order_id ) );

		$resend = $this->queue_purchase( $order_id );
		$this->tracker->track_purchase( $order_id );

		$this->assertNotSame( 0, $resend );
		$this->assertCount( 1, $this->pending_sends( $order_id ) );

		$this->run_action( $resend );

		$this->assertSame( ActionScheduler_Store::STATUS_COMPLETE, $this->status( $resend ) );
		$this->assertSame( '1', (string) wc_get_order( $order_id )->get_meta( RemoteConversionTracker::ORDER_CONVERSION_TRACKED_META_KEY ) );
	}

	/**
	 * Test that loading the order confirmation page after a successful send doesn't queue it again.
	 */
	public function test_reload_after_successful_send_does_not_queue_again(): void {
		$this->client->method( 'proxy_post' )->willReturn( new WP_REST_Response( array(), 200 ) );
		$order_id = wc_create_order()->get_id();
		$this->run_action( $this->queue_purchase( $order_id ) );

		$this->tracker->track_purchase( $order_id );

		$this->assertCount( 0, $this->pending_sends( $order_id ) );
	}

	/**
	 * Test that a failed add-to-cart send completes.
	 */
	public function test_failed_add_to_cart_completes(): void {
		$this->client->expects( $this->once() )->method( 'proxy_post' )->willReturn( $this->transport_error() );
		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->save();

		$this->tracker->track_add_to_cart( $product->get_id(), 1 );
		$action_id = key( $this->pending_sends() );

		$this->run_action( $action_id );

		$this->assertSame( ActionScheduler_Store::STATUS_COMPLETE, $this->status( $action_id ) );
	}

	/**
	 * Test that a failed non-Purchase send completes even when its args carry an order ID.
	 */
	public function test_failed_non_purchase_send_with_order_id_completes(): void {
		$this->client->expects( $this->once() )->method( 'proxy_post' )->willReturn( $this->transport_error() );
		$order_id  = wc_create_order()->get_id();
		$action_id = as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array(
				'event_payload' => array( 'event_name' => 'ADD_CART' ),
				'args'          => array( 'order_id' => $order_id ),
			),
			Config::PLUGIN_SLUG
		);

		$this->run_action( $action_id );

		$this->assertSame( ActionScheduler_Store::STATUS_COMPLETE, $this->status( $action_id ) );
	}

	/**
	 * Test that firing the send hook directly never throws and still runs later callbacks.
	 */
	public function test_direct_send_hook_never_throws(): void {
		$this->client->expects( $this->once() )->method( 'proxy_post' )->willReturn( $this->transport_error() );
		$order_id    = wc_create_order()->get_id();
		$later_calls = 0;
		add_action(
			Helper::with_prefix( 'send_conversion_event' ),
			function () use ( &$later_calls ) {
				++$later_calls;
			},
			20
		);

		do_action( Helper::with_prefix( 'send_conversion_event' ), array( 'event_name' => 'PURCHASE' ), array( 'order_id' => $order_id ) );

		$this->assertSame( 1, $later_calls );
	}

	/**
	 * Test that firing the send hook directly after a queued run in the same request never throws.
	 *
	 * @dataProvider queued_run_result_provider
	 *
	 * @param bool $queued_run_fails Whether the earlier queued send fails.
	 */
	public function test_direct_send_hook_after_queued_run_never_throws( bool $queued_run_fails ): void {
		$this->client->expects( $this->exactly( 2 ) )->method( 'proxy_post' )->willReturnOnConsecutiveCalls(
			$queued_run_fails ? $this->transport_error() : new WP_REST_Response( array(), 200 ),
			$this->transport_error()
		);
		$this->run_action( $this->queue_purchase( wc_create_order()->get_id() ) );

		do_action( Helper::with_prefix( 'send_conversion_event' ), array( 'event_name' => 'PURCHASE' ), array( 'order_id' => wc_create_order()->get_id() ) );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Outcomes of the queued send that runs before the direct hook call.
	 *
	 * @return array
	 */
	public function queued_run_result_provider(): array {
		return array(
			'after a failed queued send'     => array( true ),
			'after a successful queued send' => array( false ),
		);
	}

	/**
	 * Test that a failed synchronous send never throws.
	 */
	public function test_failed_synchronous_send_never_throws(): void {
		$this->client->expects( $this->once() )->method( 'proxy_post' )->willReturn( $this->transport_error() );

		$this->tracker->track_page_view( 'page-view-event' );
	}

	/**
	 * Test that a failure in one action doesn't fail the next action in the same queue run.
	 */
	public function test_failure_does_not_leak_into_next_action(): void {
		$this->client->method( 'proxy_post' )->willReturnOnConsecutiveCalls(
			$this->http_error( 400 ),
			new WP_REST_Response( array(), 200 )
		);
		$failing    = $this->queue_purchase( wc_create_order()->get_id() );
		$succeeding = $this->queue_purchase( wc_create_order()->get_id() );

		$this->run_action( $failing );
		$this->run_action( $succeeding );

		$this->assertSame( ActionScheduler_Store::STATUS_FAILED, $this->status( $failing ) );
		$this->assertSame( ActionScheduler_Store::STATUS_COMPLETE, $this->status( $succeeding ) );
	}

	/**
	 * Test that the failure message names the order and status without the access token.
	 *
	 * The error carries the request URL, as a real WcsClient error can, so a message built
	 * from the error's text or data would leak the token.
	 */
	public function test_failure_message_omits_access_token(): void {
		$error = new WP_Error(
			Helper::with_prefix( 'request_failed' ),
			'Request to /conversions/v3/pixel_456/events?access_token=token_abc failed',
			array(
				'response' => array(
					'code'    => 400,
					'message' => '',
				),
				'body'     => '',
				'url'      => 'https://example.com/conversions/v3/pixel_456/events?access_token=token_abc',
			)
		);
		$this->client->method( 'proxy_post' )->willReturn( $error );
		$order_id  = wc_create_order()->get_id();
		$action_id = $this->queue_purchase( $order_id );

		$this->run_action( $action_id );

		$log = $this->failure_log( $action_id );
		$this->assertStringContainsString( "order {$order_id}", $log );
		$this->assertStringContainsString( 'HTTP 400', $log );
		$this->assertStringContainsString( 'queued again', $log );
		$this->assertStringNotContainsString( 'token_abc', $log );
	}

	/**
	 * Queues a Purchase send through the tracker and returns its action ID.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return int
	 */
	private function queue_purchase( int $order_id ): int {
		$this->tracker->track_purchase( $order_id );

		return (int) key( $this->pending_sends( $order_id ) );
	}

	/**
	 * Runs an action through the Action Scheduler queue runner.
	 *
	 * @param int $action_id Action ID.
	 */
	private function run_action( int $action_id ): void {
		ActionScheduler::runner()->process_action( $action_id, 'PHPUnit' );
	}

	/**
	 * Returns an action's status.
	 *
	 * @param int $action_id Action ID.
	 * @return string
	 */
	private function status( int $action_id ): string {
		return ActionScheduler::store()->get_status( $action_id );
	}

	/**
	 * Returns pending send actions, keyed by action ID, optionally for one order.
	 *
	 * @param int|null $order_id WooCommerce order ID, or null for every send.
	 * @return \ActionScheduler_Action[]
	 */
	private function pending_sends( ?int $order_id = null ): array {
		$actions = as_get_scheduled_actions(
			array(
				'hook'     => Helper::with_prefix( 'send_conversion_event' ),
				'group'    => Config::PLUGIN_SLUG,
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1,
			),
			'objects'
		);

		if ( null === $order_id ) {
			return $actions;
		}

		return array_filter(
			$actions,
			fn( $action ) => ( $action->get_args()['args']['order_id'] ?? 0 ) === $order_id
		);
	}

	/**
	 * Returns the Action Scheduler log messages for an action.
	 *
	 * @param int $action_id Action ID.
	 * @return string
	 */
	private function failure_log( int $action_id ): string {
		$messages = array_map(
			fn( $entry ) => $entry->get_message(),
			\ActionScheduler_Logger::instance()->get_logs( $action_id )
		);

		return implode( "\n", $messages );
	}

	/**
	 * Builds a transport error such as a timeout.
	 *
	 * @return WP_Error
	 */
	private function transport_error(): WP_Error {
		return new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
	}

	/**
	 * Builds the WP_Error WcsClient returns for a non-2xx response.
	 *
	 * @param int $code HTTP status code.
	 * @return WP_Error
	 */
	private function http_error( int $code ): WP_Error {
		return new WP_Error(
			Helper::with_prefix( 'request_failed' ),
			'Request failed',
			array(
				'response' => array(
					'code'    => $code,
					'message' => '',
				),
				'body'     => '',
			)
		);
	}
}
