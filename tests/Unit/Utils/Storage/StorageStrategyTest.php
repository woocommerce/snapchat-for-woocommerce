<?php
/**
 * Basic existence test for the StorageStrategy interface.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Utils
 */

namespace SnapchatForWooCommerce\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\StorageStrategy
 */
class StorageStrategyTest extends TestCase {

	public function test_interface_exists(): void {
		$this->assertTrue( interface_exists( \SnapchatForWooCommerce\Utils\Storage\StorageStrategy::class ) );
	}
}
