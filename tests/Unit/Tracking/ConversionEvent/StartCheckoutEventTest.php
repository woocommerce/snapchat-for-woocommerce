<?php

use PHPUnit\Framework\TestCase;
use SnapchatForWooCommerce\Tracking\ConversionEvent\StartCheckoutEvent;

class StartCheckoutEventTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( null === WC()->cart ) {
			wc_load_cart();
		}

		WC()->cart->empty_cart();
	}

	/**
	 * Test that build_payload() returns expected structure and values.
	 */
	public function test_build_payload_contains_expected_structure() {
		// Create and add a product to cart
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 19.99 );
		$product->set_sku( 'TEST-SKU-123' );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), 2 );

		$event   = new StartCheckoutEvent( WC()->cart );
		$payload = $event->build_payload();

		// Basic keys
		$this->assertIsArray( $payload );
		$this->assertArrayHasKey( 'event_name', $payload );
		$this->assertEquals( 'START_CHECKOUT', $payload['event_name'] );

		$this->assertArrayHasKey( 'event_time', $payload );
		$this->assertArrayHasKey( 'custom_data', $payload );
		$this->assertIsArray( $payload['custom_data'] );

		// Inside custom_data
		$custom = $payload['custom_data'];

		$this->assertArrayHasKey( 'contents', $custom );
		$this->assertIsArray( $custom['contents'] );
		$this->assertCount( 1, $custom['contents'] );

		$item = $custom['contents'][0];
		$this->assertEquals( (string) $product->get_id(), $item['id'] );
		$this->assertEquals( '2', $item['quantity'] );
		$this->assertEquals( (string) $product->get_price(), $item['item_price'] );

		$this->assertArrayHasKey( 'content_ids', $custom );
		$this->assertContains( $product->get_sku(), $custom['content_ids'] );

		$this->assertEquals( get_woocommerce_currency(), $custom['currency'] );
		$this->assertEquals( '2', $custom['num_items'] );
		$this->assertEquals( wc_format_decimal( WC()->cart->total ), $custom['value'] );
	}
}
