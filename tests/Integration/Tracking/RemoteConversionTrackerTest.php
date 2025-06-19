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
		$this->tracker = new RemoteConversionTracker( $this->client );
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

		$this->tracker->send( array( 'event_name' => 'test' ) );
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
			->with(
				'',
				$this->callback( fn( $path ) => str_starts_with( $path, 'pixel_456/events?access_token=token_abc' ) ),
				$this->callback(
					function ( $payload ) {
						return isset( $payload['data'][0]['user_data']['client_ip_address'] )
						&& $payload['data'][0]['user_data']['client_ip_address'] === '203.0.113.55'
						&& $payload['data'][0]['user_data']['client_user_agent'] === 'PHPUnitTestRunner';
					}
				),
				'conversions'
			);

		$this->tracker->send( array( 'event_name' => 'Purchase' ) );
	}
}
