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
	 * Sets up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * Test that get_purchase_id returns a non-empty string.
	 *
	 * @since 0.1.0
	 */
	public function test_get_purchase_id_returns_non_empty_string(): void {
		$purchase_id = EventIdRegistry::get_purchase_id();

		$this->assertIsString( $purchase_id );
		$this->assertNotEmpty( $purchase_id );
	}

	/**
	 * Test that get_purchase_id returns the same ID on multiple calls.
	 *
	 * This tests the static caching behavior to ensure idempotency
	 * within a single request lifecycle.
	 *
	 * @since x.x.x
	 */
	public function test_get_purchase_id_returns_same_id_on_multiple_calls(): void {
		$first_call  = EventIdRegistry::get_purchase_id();
		$second_call = EventIdRegistry::get_purchase_id();
		$third_call  = EventIdRegistry::get_purchase_id();

		$this->assertSame( $first_call, $second_call );
		$this->assertSame( $second_call, $third_call );
	}

	/**
	 * Test that get_purchase_id is consistent across the request.
	 *
	 * @since x.x.x
	 */
	public function test_get_purchase_id_maintains_consistency(): void {
		$ids = array();

		// Call the method 10 times
		for ( $i = 0; $i < 10; $i++ ) {
			$ids[] = EventIdRegistry::get_purchase_id();
		}

		// All IDs should be identical
		$unique_ids = array_unique( $ids );
		$this->assertCount( 1, $unique_ids );
	}
}
