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
		$args    = array( 'order_id' => wc_create_order()->get_id() );
		$this->tracker->send( $payload, $args );

		$this->assertSame( array( array( $payload, $args ) ), $sent->calls );
		$this->assertSame( array(), $failed->calls );
	}

	/**
	 * Test that a transport error fires conversion_failed with the error and not conversion_sent.
	 */
	public function test_send_fires_conversion_failed_on_transport_error(): void {
		$this->set_credentials();
		$error = new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		$this->client->method( 'proxy_post' )->willReturn( $error );

		$sent   = $this->record_action( 'conversion_sent' );
		$failed = $this->record_action( 'conversion_failed' );

		$payload = array( 'event_name' => 'PURCHASE' );
		$args    = array( 'order_id' => wc_create_order()->get_id() );
		$this->tracker->send( $payload, $args );

		$this->assertSame( array(), $sent->calls );
		$this->assertSame( array( array( $payload, $args, $error ) ), $failed->calls );
	}

	/**
	 * Test that a non-2xx response wrapped in a WP_Error fires conversion_failed.
	 */
	public function test_send_fires_conversion_failed_on_non_2xx_response(): void {
		$this->set_credentials();
		$this->client->method( 'proxy_post' )->willReturn( $this->http_error( 500 ) );

		$sent   = $this->record_action( 'conversion_sent' );
		$failed = $this->record_action( 'conversion_failed' );

		$this->tracker->send( array( 'event_name' => 'PURCHASE' ), array( 'order_id' => wc_create_order()->get_id() ) );

		$this->assertSame( array(), $sent->calls );
		$this->assertCount( 1, $failed->calls );
	}

	/**
	 * Sets a CAPI token and pixel ID so send() reaches the client.
	 */
	private function set_credentials(): void {
		Options::set( OptionDefaults::CONVERSION_ACCESS_TOKEN, 'token_abc' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel_456' );
	}

	/**
	 * Builds the WP_Error WcsClient returns for a non-2xx response.
	 *
	 * @param int    $code HTTP status code.
	 * @param string $body Response body.
	 * @return \WP_Error
	 */
	private function http_error( int $code, string $body = '' ): \WP_Error {
		return new \WP_Error(
			Helper::with_prefix( 'request_failed' ),
			'Request failed',
			array(
				'response' => array(
					'code'    => $code,
					'message' => '',
				),
				'body'     => $body,
			)
		);
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
}
