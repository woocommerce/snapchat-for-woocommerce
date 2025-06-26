<?php
/**
 * Tests for the PurchaseEvent class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Tracking\ConversionEvent
 */

namespace SnapchatForWooCommerce\Tests\Integration\Tracking\ConversionEvent;

use WC_Product_Simple;
use SnapchatForWooCommerce\Tracking\ConversionEvent\PurchaseEvent;
use WP_UnitTestCase;

/**
 * @covers \SnapchatForWooCommerce\Tracking\ConversionEvent\PurchaseEvent
 */
class PurchaseEventTest extends WP_UnitTestCase {

	/**
	 * Set up environment for the test.
	 */
	public function set_up(): void {
		parent::set_up();

		$_SERVER['HTTP_REFERER'] = 'https://example.com/order-complete';
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		unset( $_SERVER['HTTP_REFERER'] );
		parent::tear_down();
	}

	/**
	 * Tests that the payload contains valid purchase data.
	 */
	public function test_build_payload_contains_expected_fields(): void {
		// Create and save a simple product.
		$product_one = new WC_Product_Simple();
		$product_one->set_name( 'Product One' );
		$product_one->set_regular_price( 20 );
		$product_one->save();

		$product_two = new WC_Product_Simple();
		$product_two->set_name( 'Product Two' );
		$product_two->set_regular_price( 15 );
		$product_two->save();

		$order = wc_create_order(
			array(
				'status'      => 'pending',
				'customer_id' => 1,
			)
		);

		$order->add_product( $product_one, 1 );
		$order->add_product( $product_two, 2 );

		// Optionally, add shipping manually
		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_title( 'Flat Rate' );
		$shipping_item->set_method_id( 'flat_rate' );
		$shipping_item->set_total( 10 ); // shipping cost
		$order->add_item( $shipping_item );

		// Finalize order totals
		$order->calculate_totals();

		// Build the payload.
		$event   = new PurchaseEvent( $order->get_id() );
		$payload = $event->build_payload();

		// Assertions.
		$this->assertIsArray( $payload );
		$this->assertSame( 'PURCHASE', $payload['event_name'] );
		$this->assertSame( 'WEB', $payload['action_source'] );
		$this->assertNotEmpty( $payload['event_id'] );
		$this->assertSame( $order->get_checkout_order_received_url(), $payload['event_source_url'] );

		$this->assertIsArray( $payload['custom_data'] );
		$this->assertSame( $order->get_currency(), $payload['custom_data']['currency'] );
		$this->assertEquals( $order->get_total(), $payload['custom_data']['value'] );

		$this->assertIsArray( $payload['custom_data']['contents'] );
		$this->assertCount( 2, $payload['custom_data']['contents'] );

		$item_1 = $payload['custom_data']['contents'][0];

		$this->assertSame( (string) $product_one->get_id(), $item_1['id'] );
		$this->assertSame( '1', $item_1['quantity'] );
		$this->assertSame( (string) $product_one->get_price(), $item_1['item_price'] );

		$item_2 = $payload['custom_data']['contents'][1];

		$this->assertSame( (string) $product_two->get_id(), $item_2['id'] );
		$this->assertSame( '2', $item_2['quantity'] );
		$this->assertSame( (string) $product_two->get_price(), $item_2['item_price'] );
	}

	/**
	 * Tests that an invalid order ID returns an empty payload.
	 */
	public function test_build_payload_returns_empty_if_order_not_found(): void {
		$event   = new PurchaseEvent( 999999 ); // unlikely to exist
		$payload = $event->build_payload();

		$this->assertSame( array(), $payload );
	}
}
