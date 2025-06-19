<?php
/**
 * Tests for the ConversionTrackingService class.
 *
 * @package SnapchatForWooCommerce\Tests\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Tracking;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Tracking\ConversionTrackingService;
use SnapchatForWooCommerce\Tracking\ConversionTrackerInterface;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * @covers \SnapchatForWooCommerce\Tracking\ConversionTrackingService
 */
class ConversionTrackingServiceTest extends WP_UnitTestCase {

	/**
	 * Conversion tracker mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject&ConversionTrackerInterface
	 */
	protected $tracker;

	/**
	 * Service under test.
	 *
	 * @var ConversionTrackingService
	 */
	protected $service;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->tracker = $this->createMock( ConversionTrackerInterface::class );
		$this->service = new ConversionTrackingService( $this->tracker );
		$this->service->register_hooks();
	}

	/**
	 * Test that WooCommerce purchase hook is registered.
	 */
	public function test_woocommerce_thankyou_hook_is_registered(): void {
		$callback = has_action( 'woocommerce_thankyou', [ $this->service, 'handle_purchase' ] );
		$this->assertNotFalse( $callback, 'Expected woocommerce_thankyou hook to be registered.' );
	}

	/**
	 * Test that WooCommerce add_to_cart hook is registered.
	 */
	public function test_woocommerce_add_to_cart_hook_is_registered(): void {
		$callback = has_action( 'woocommerce_add_to_cart', [ $this->service, 'handle_add_to_cart' ] );
		$this->assertNotFalse( $callback, 'Expected woocommerce_add_to_cart hook to be registered.' );
	}

	/**
	 * Test that action for async conversion event is registered.
	 */
	public function test_conversion_event_hook_is_registered(): void {
		$hook = Helper::with_prefix( 'send_conversion_event' );
		$callback = has_action( $hook, [ $this->tracker, 'send' ] );
		$this->assertNotFalse( $callback, "Expected {$hook} to be registered with the tracker." );
	}

	/**
	 * Test handle_purchase calls tracker's track_purchase.
	 */
	public function test_handle_purchase_executes_tracker(): void {
		$this->tracker->expects( $this->once() )
			->method( 'track_purchase' )
			->with( 101 );

		$this->service->handle_purchase( 101 );
	}

	/**
	 * Test handle_add_to_cart calls tracker's track_add_to_cart.
	 */
	public function test_handle_add_to_cart_executes_tracker(): void {
		$this->tracker->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with( 222, 3 );

		$this->service->handle_add_to_cart( 'somekey', 222, 3 );
	}
}
