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
	 * Test that a timed-out Purchase ends Failed, stays untracked and schedules attempt 2.
	 */
	public function test_timed_out_purchase_fails_and_schedules_retry(): void {
		$this->client->method( 'proxy_post' )->willReturn( $this->transport_error() );
		$order_id  = wc_create_order()->get_id();
		$action_id = $this->queue_purchase( $order_id );

		$this->run_action( $action_id );

		$this->assertSame( ActionScheduler_Store::STATUS_FAILED, $this->status( $action_id ) );
		$this->assertEmpty( wc_get_order( $order_id )->get_meta( RemoteConversionTracker::ORDER_CONVERSION_TRACKED_META_KEY ) );

		$retries = $this->pending_sends( $order_id );
		$this->assertCount( 1, $retries );
		$retry = reset( $retries );
		$this->assertSame( 2, $retry->get_args()['args']['attempt'] );
		$this->assertEqualsWithDelta( time() + 5 * MINUTE_IN_SECONDS, $retry->get_schedule()->get_date()->getTimestamp(), 10 );
	}

	/**
	 * Test that a rejected Purchase ends Failed without a retry.
	 *
	 * @dataProvider permanent_error_provider
	 *
	 * @param WP_Error $error Error returned by the WCS client.
	 */
	public function test_permanently_failed_purchase_is_not_retried( WP_Error $error ): void {
		$this->client->method( 'proxy_post' )->willReturn( $error );
		$order_id  = wc_create_order()->get_id();
		$action_id = $this->queue_purchase( $order_id );

		$this->run_action( $action_id );

		$this->assertSame( ActionScheduler_Store::STATUS_FAILED, $this->status( $action_id ) );
		$this->assertCount( 0, $this->pending_sends( $order_id ) );
	}

	/**
	 * Errors that must not be retried.
	 *
	 * @return array
	 */
	public function permanent_error_provider(): array {
		return array(
			'HTTP 400'         => array( $this->http_error( 400 ) ),
			'sandbox block'    => array( new WP_Error( 'snapchat_sandbox_remote_request_blocked', 'Blocked', array( 'status' => 403 ) ) ),
			'no Jetpack token' => array( new WP_Error( 'missing_token' ) ),
		);
	}

	/**
	 * Test that temporary HTTP failures are retried.
	 *
	 * @dataProvider temporary_status_provider
	 *
	 * @param int $status HTTP status returned through the WCS proxy.
	 */
	public function test_temporary_http_failure_is_retried( int $status ): void {
		$this->client->method( 'proxy_post' )->willReturn( $this->http_error( $status ) );
		$order_id  = wc_create_order()->get_id();
		$action_id = $this->queue_purchase( $order_id );

		$this->run_action( $action_id );

		$this->assertSame( ActionScheduler_Store::STATUS_FAILED, $this->status( $action_id ) );
		$this->assertCount( 1, $this->pending_sends( $order_id ) );
	}

	/**
	 * Temporary HTTP statuses.
	 *
	 * @return array
	 */
	public function temporary_status_provider(): array {
		return array(
			'HTTP 429' => array( 429 ),
			'HTTP 503' => array( 503 ),
		);
	}

	/**
	 * Test the delay before each retry attempt.
	 *
	 * @dataProvider retry_delay_provider
	 *
	 * @param int $failed_attempt Attempt that fails.
	 * @param int $delay          Expected seconds until the next attempt.
	 */
	public function test_retry_delay_grows_with_each_attempt( int $failed_attempt, int $delay ): void {
		$this->client->method( 'proxy_post' )->willReturn( $this->transport_error() );
		$order_id  = wc_create_order()->get_id();
		$action_id = $this->queue_send( $order_id, $failed_attempt );

		$this->run_action( $action_id );

		$retries = $this->pending_sends( $order_id );
		$this->assertCount( 1, $retries );
		$retry = reset( $retries );
		$this->assertSame( $failed_attempt + 1, $retry->get_args()['args']['attempt'] );
		$this->assertEqualsWithDelta( time() + $delay, $retry->get_schedule()->get_date()->getTimestamp(), 10 );
	}

	/**
	 * Failed attempts and the delay before the following one.
	 *
	 * @return array
	 */
	public function retry_delay_provider(): array {
		return array(
			'after attempt 1' => array( 1, 5 * MINUTE_IN_SECONDS ),
			'after attempt 2' => array( 2, 30 * MINUTE_IN_SECONDS ),
			'after attempt 3' => array( 3, 2 * HOUR_IN_SECONDS ),
			'after attempt 4' => array( 4, 12 * HOUR_IN_SECONDS ),
		);
	}

	/**
	 * Test that the fifth failed attempt gives up and leaves the order untracked.
	 */
	public function test_last_attempt_gives_up(): void {
		$this->client->method( 'proxy_post' )->willReturn( $this->transport_error() );
		$order_id  = wc_create_order()->get_id();
		$action_id = $this->queue_send( $order_id, 5 );

		$this->run_action( $action_id );

		$this->assertSame( ActionScheduler_Store::STATUS_FAILED, $this->status( $action_id ) );
		$this->assertCount( 0, $this->pending_sends( $order_id ) );
		$this->assertEmpty( wc_get_order( $order_id )->get_meta( RemoteConversionTracker::ORDER_CONVERSION_TRACKED_META_KEY ) );
	}

	/**
	 * Test that a successful retry marks the order tracked and resends the original payload.
	 */
	public function test_successful_retry_marks_order_tracked(): void {
		$order_id = wc_create_order()->get_id();
		$sent     = array();
		$this->client->method( 'proxy_post' )->willReturnCallback(
			function ( $path, $payload ) use ( &$sent ) {
				$sent[] = $payload['data'][0];
				return 1 === count( $sent ) ? $this->http_error( 503 ) : new WP_REST_Response( array(), 200 );
			}
		);
		$first = $this->queue_purchase( $order_id );

		$this->run_action( $first );
		$retries = $this->pending_sends( $order_id );
		$retry   = key( $retries );
		$this->run_action( $retry );

		$this->assertSame( ActionScheduler_Store::STATUS_COMPLETE, $this->status( $retry ) );
		$this->assertSame( '1', (string) wc_get_order( $order_id )->get_meta( RemoteConversionTracker::ORDER_CONVERSION_TRACKED_META_KEY ) );
		$this->assertCount( 2, $sent );
		$this->assertSame( $sent[0]['event_time'], $sent[1]['event_time'] );
		$this->assertSame( $sent[0]['event_id'], $sent[1]['event_id'] );
	}

	/**
	 * Test that a failed follow-up schedule still leaves the action Failed.
	 */
	public function test_unschedulable_retry_still_fails_action(): void {
		$this->client->method( 'proxy_post' )->willReturn( $this->transport_error() );
		$order_id  = wc_create_order()->get_id();
		$action_id = $this->queue_purchase( $order_id );
		add_filter( 'pre_as_schedule_single_action', fn() => 0 );

		$this->run_action( $action_id );

		$this->assertSame( ActionScheduler_Store::STATUS_FAILED, $this->status( $action_id ) );
		$this->assertCount( 0, $this->pending_sends( $order_id ) );
		$this->assertStringContainsString( 'could not be scheduled', $this->failure_log( $action_id ) );
	}

	/**
	 * Test that a failed add-to-cart send completes without a retry.
	 */
	public function test_failed_add_to_cart_completes_without_retry(): void {
		$this->client->method( 'proxy_post' )->willReturn( $this->transport_error() );
		$product = new WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->save();

		$this->tracker->track_add_to_cart( $product->get_id(), 1 );
		$actions   = $this->pending_sends();
		$action_id = key( $actions );

		$this->run_action( $action_id );

		$this->assertSame( ActionScheduler_Store::STATUS_COMPLETE, $this->status( $action_id ) );
		$this->assertCount( 0, $this->pending_sends() );
	}

	/**
	 * Test that firing the send hook directly never throws or schedules a retry.
	 */
	public function test_direct_send_hook_never_throws(): void {
		$this->client->method( 'proxy_post' )->willReturn( $this->transport_error() );
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
		$this->assertCount( 0, $this->pending_sends( $order_id ) );
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
	 * Test that the failure message names the event and status without the access token.
	 */
	public function test_failure_message_omits_access_token(): void {
		$this->client->method( 'proxy_post' )->willReturn( $this->http_error( 400 ) );
		$action_id = $this->queue_purchase( wc_create_order()->get_id() );

		$this->run_action( $action_id );

		$log = $this->failure_log( $action_id );
		$this->assertStringContainsString( 'PURCHASE', $log );
		$this->assertStringContainsString( '400', $log );
		$this->assertStringNotContainsString( 'token_abc', $log );
		$this->assertStringNotContainsString( 'access_token', $log );
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
	 * Queues a Purchase send for a given attempt and returns its action ID.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @param int $attempt  Attempt number.
	 * @return int
	 */
	private function queue_send( int $order_id, int $attempt ): int {
		return as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array(
				'event_payload' => array(
					'event_name' => 'PURCHASE',
					'event_id'   => (string) $order_id,
					'event_time' => 1700000000000,
				),
				'args'          => array(
					'order_id' => $order_id,
					'attempt'  => $attempt,
				),
			),
			Config::PLUGIN_SLUG
		);
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
