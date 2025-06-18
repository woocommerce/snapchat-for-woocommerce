<?php
/**
 * Basic existence test for the StorageStrategyInterface interface.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Utils
 */

namespace SnapchatForWooCommerce\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\StorageStrategyInterface
 */
class StorageStrategyInterfaceTest extends TestCase {

	public function test_interface_exists(): void {
		$this->assertTrue( interface_exists( \SnapchatForWooCommerce\Utils\Storage\StorageStrategyInterface::class ) );
	}
}
