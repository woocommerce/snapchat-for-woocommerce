<?php
/**
 * Unit test for the ConversionTrackingService.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

/**
 * Override check_ajax_referer() and filter_input() in this namespace for testing.
 */
function check_ajax_referer( $action = -1, $query_arg = false, $stop = true ) {
	return true;
}

function filter_input( $type, $var_name, $filter = FILTER_DEFAULT, $options = [] ) {
	return $_POST[ $var_name ] ?? null;
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

		$service->handle_single_product_add_to_cart( 'key', 55, 2, 0 );

		unset( $_POST );
	}

	public function test_handle_async_add_to_cart_requires_nonce_and_delegates(): void {
		$service = new ConversionTrackingService( $this->mock_tracker );

		// Simulate JSON payload
		$payload = wp_json_encode( array(
			'product_id' => 777,
			'quantity'   => 4,
			'event_id'   => 'uuid-999',
		) );

		$_POST['security'] = wp_create_nonce( 'capi_nonce' );
		$_POST['payload']  = wp_slash( $payload );

		$this->mock_tracker
			->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with( 777, 4, 'uuid-999' );

		$service->handle_async_add_to_cart();

		unset( $_POST );
	}

	/**
	 * Test that malformed data from the conversion_failed hook is ignored rather than fatal.
	 *
	 * @dataProvider malformed_failure_provider
	 *
	 * @param mixed $event_payload Payload passed through the hook.
	 * @param mixed $args          Args passed through the hook.
	 * @param mixed $error         Error passed through the hook.
	 */
	public function test_handle_failed_conversion_ignores_malformed_hook_data( $event_payload, $args, $error ): void {
		$service = new ConversionTrackingService( $this->mock_tracker );
		$service->start_queued_send( 1 );

		$service->handle_failed_conversion( $event_payload, $args, $error );
		$service->fail_queued_purchase();

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Malformed values another callback could pass through the conversion_failed hook.
	 *
	 * @return array
	 */
	public function malformed_failure_provider(): array {
		$error = new \WP_Error( 'http_request_failed', 'Timed out' );

		return array(
			'args not an array'     => array( array( 'event_name' => 'PURCHASE' ), 'order_id=1', $error ),
			'non-numeric attempt'   => array( array( 'event_name' => 'PURCHASE' ), array( 'order_id' => 'x', 'attempt' => 'abc' ), $error ),
			'error not a WP_Error'  => array( array( 'event_name' => 'PURCHASE' ), array( 'order_id' => 1 ), 'timeout' ),
			'payload not an array'  => array( 'PURCHASE', array( 'order_id' => 1 ), $error ),
		);
	}
}
