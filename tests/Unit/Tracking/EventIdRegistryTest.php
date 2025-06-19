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

/**
 * @covers \SnapchatForWooCommerce\Tracking\EventIdRegistry
 */
final class EventIdRegistryTest extends TestCase {

	/**
	 * Reset state before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset private static properties using reflection.
		$ref = new \ReflectionClass( EventIdRegistry::class );

		$cart_ids = $ref->getProperty( 'add_to_cart_ids' );
		$cart_ids->setAccessible( true );
		$cart_ids->setValue( array() );

		$purchase_id = $ref->getProperty( 'purchase_id' );
		$purchase_id->setAccessible( true );
		$purchase_id->setValue( null );
	}

	/**
	 * Test that the same product returns the same add-to-cart event ID.
	 */
	public function test_get_add_to_cart_id_is_stable_for_same_product(): void {
		$product_id = 123;

		$first  = EventIdRegistry::get_add_to_cart_id( $product_id );
		$second = EventIdRegistry::get_add_to_cart_id( $product_id );

		$this->assertSame( $first, $second, 'Expected same ID for same product' );
		$this->assertMatchesRegularExpression( '/^[0-9a-f\-]{36}$/i', $first );
	}

	/**
	 * Test that different product IDs return different event IDs.
	 */
	public function test_get_add_to_cart_id_is_unique_for_different_products(): void {
		$id1 = EventIdRegistry::get_add_to_cart_id( 101 );
		$id2 = EventIdRegistry::get_add_to_cart_id( 102 );

		$this->assertNotSame( $id1, $id2, 'Expected different IDs for different products' );
	}

	/**
	 * Test that get_purchase_id() returns the same value across calls.
	 */
	public function test_get_purchase_id_is_consistent(): void {
		$id1 = EventIdRegistry::get_purchase_id();
		$id2 = EventIdRegistry::get_purchase_id();

		$this->assertSame( $id1, $id2, 'Expected same purchase ID on multiple calls' );
		$this->assertMatchesRegularExpression( '/^[0-9a-f\-]{36}$/i', $id1 );
	}
}
