<?php
/**
 * Unit test for the ConversionTrackingService.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

/**
 * Override check_ajax_referer() in this namespace for testing.
 */
function check_ajax_referer( $action = -1, $query_arg = false, $stop = true ) {
	return true;
}

namespace SnapchatForWooCommerce\Tests\Unit\Tracking;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Tracking\ConversionTrackingService;
use SnapchatForWooCommerce\Tracking\ConversionTrackerInterface;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * @covers \SnapchatForWooCommerce\Tracking\ConversionTrackingService
 */
final class ConversionTrackingServiceTest extends WP_UnitTestCase {

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|ConversionTrackerInterface
	 */
	private $mock_tracker;

	public function set_up(): void {
		parent::set_up();
		$this->mock_tracker = $this->createMock( ConversionTrackerInterface::class );
	}

	public function test_handle_purchase_delegates_to_tracker(): void {
		$service = new ConversionTrackingService( $this->mock_tracker );

		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_purchase' )
			->with( 123 );

		$service->handle_purchase( 123 );
	}

	public function test_handle_single_product_add_to_cart_delegates_with_event_id(): void {
		$service = new ConversionTrackingService( $this->mock_tracker );

		$_POST[ Helper::with_prefix( 'event_id' ) ] = 'abc-uuid';

		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with( 55, 2, 'abc-uuid' );

		$service->handle_single_product_add_to_cart( 'key', 55, 2 );

		unset( $_POST );
	}

	public function test_handle_async_add_to_cart_requires_nonce_and_delegates(): void {
		// Prevent wp_die from exiting the test on nonce check.
		tests_add_filter( 'wp_die_handler', '__return_false' );

		$service = new ConversionTrackingService( $this->mock_tracker );

		$_POST['security']   = wp_create_nonce( 'capi_nonce' );
		$_POST['product_id'] = 777;
		$_POST['quantity']   = 4;
		$_POST['event_id']   = 'uuid-999';

		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with( 777, 4, 'uuid-999' );

		$service->handle_async_add_to_cart();

		unset( $_POST );
	}
}
