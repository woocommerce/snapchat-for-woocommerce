<?php
/**
 * Integration test for AddToCartEvent class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Tracking\ConversionEvent
 */

declare( strict_types=1 );

namespace SnapchatForWooCommerce\Tests\Integration\Tracking\ConversionEvent;

use WP_UnitTestCase;
use WC_Product_Simple;
use SnapchatForWooCommerce\Tracking\ConversionEvent\AddToCartEvent;

/**
 * @covers \SnapchatForWooCommerce\Tracking\ConversionEvent\AddToCartEvent
 */
final class AddToCartEventTest extends WP_UnitTestCase {

	/**
	 * Sample quantity.
	 *
	 * @var int
	 */
	protected $quantity = 3;

	/**
	 * Sets a referer URL before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		$_SERVER['HTTP_REFERER'] = 'https://example.com/cart';
	}

	/**
	 * Unsets referer after test.
	 */
	public function tear_down(): void {
		unset( $_SERVER['HTTP_REFERER'] );
		parent::tear_down();
	}

	/**
	 * Test that build_payload() returns expected structure and values.
	 */
	public function test_build_payload_returns_expected_data(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Product One' );
		$product->set_regular_price( 20 );
		$product->save();

		$event   = new AddToCartEvent( $product->get_id(), $this->quantity );
		$payload = $event->build_payload( array( 'event_id' => 'abc_123' ) );

		$this->assertIsArray( $payload );

		$this->assertSame( 'ADD_CART', $payload['event_name'] );
		$this->assertSame( 'WEB', $payload['action_source'] );
		$this->assertSame( 'https://example.com/cart', $payload['event_source_url'] );

		$this->assertArrayHasKey( 'event_time', $payload );
		$this->assertIsInt( $payload['event_time'] );

		$this->assertArrayHasKey( 'event_id', $payload );

		$this->assertSame( array(), $payload['user_data'] );

		$this->assertArrayHasKey( 'custom_data', $payload );
		$this->assertArrayHasKey( 'contents', $payload['custom_data'] );
		$this->assertCount( 1, $payload['custom_data']['contents'] );

		$contents = $payload['custom_data']['contents'][0];
		$this->assertSame( (string) $product->get_id(), $contents['id'] );
		$this->assertSame( (string) $this->quantity, $contents['quantity'] );
	}
}
