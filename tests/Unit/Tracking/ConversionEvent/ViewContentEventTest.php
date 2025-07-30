<?php

use PHPUnit\Framework\TestCase;
use SnapchatForWooCommerce\Tracking\ConversionEvent\ViewContentEvent;

class ViewContentEventTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Test that build_payload() returns expected structure and values.
	 */
	public function test_build_payload_for_simple_product() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Simple Product' );
		$product->set_sku( 'SIMPLE-SKU' );
		$product->set_price( 10.00 );
		$product->save();

		$event   = new ViewContentEvent( $product->get_id() );
		$payload = $event->build_payload();

		$this->assertIsArray( $payload );
		$this->assertEquals( 'VIEW_CONTENT', $payload['event_name'] );
		$this->assertArrayHasKey( 'event_time', $payload );
		$this->assertEquals( 'WEB', $payload['action_source'] );

		$this->assertArrayHasKey( 'custom_data', $payload );
		$custom = $payload['custom_data'];

		$this->assertEquals( array( $product->get_sku() ), $custom['content_ids'] );
		$this->assertEquals( 'product', $custom['content_type'] );
		$this->assertEquals( get_woocommerce_currency(), $custom['currency'] );
	}

	/**
	 * Test that build_payload() handles non-simple products correctly.
	 */
	public function test_build_payload_for_grouped_product_sets_product_group_type() {
		$grouped = new WC_Product_Grouped();
		$grouped->set_name( 'Grouped Product' );
		$grouped->set_sku( 'GROUPED-SKU' );
		$grouped->save();

		$event   = new ViewContentEvent( $grouped->get_id() );
		$payload = $event->build_payload();

		$this->assertEquals( 'product_group', $payload['custom_data']['content_type'] );
	}

	/**
	 * Test that build_payload() returns empty array for non-existent product.
	 */
	public function test_build_payload_for_invalid_product_returns_empty_array() {
		$invalid_product_id = 999999; // ID that doesn't exist
		$event              = new ViewContentEvent( $invalid_product_id );
		$payload            = $event->build_payload();

		$this->assertEmpty( $payload );
	}
}
