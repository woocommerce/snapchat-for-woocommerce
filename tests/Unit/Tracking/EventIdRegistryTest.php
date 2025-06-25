<?php
/**
 * Unit tests for the EventIdRegistry class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Tracking
 */

declare( strict_types=1 );

namespace SnapchatForWooCommerce\Tests\Unit\Tracking;

use PHPUnit\Framework\TestCase;
use SnapchatForWooCommerce\Tracking\EventIdRegistry;
use WC_Helper_Order;

/**
 * @covers \SnapchatForWooCommerce\Tracking\EventIdRegistry
 */
final class EventIdRegistryTest extends TestCase {

	/**
	 * The ID of the order created during the test.
	 *
	 * @var int
	 */
	private $order_id;

	/**
	 * Sets up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a simple product using WC helper.
		$product = \WC_Helper_Product::create_simple_product();

		// Create an order and add the product.
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		$this->order_id = $order->get_id();
	}

	/**
	 * Tests get_purchase_id() returns correct order key for a valid order.
	 */
	public function test_get_purchase_id_returns_order_key_for_valid_order(): void {
		$order    = wc_get_order( $this->order_id );
		$expected = $order->get_order_key();
		$actual   = EventIdRegistry::get_purchase_id( $this->order_id );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Tests get_purchase_id() returns empty string for an invalid order ID.
	 */
	public function test_get_purchase_id_returns_empty_string_for_invalid_order(): void {
		$actual = EventIdRegistry::get_purchase_id( 999999 ); // Assuming this ID doesn't exist.
		$this->assertSame( '', $actual );
	}
}
