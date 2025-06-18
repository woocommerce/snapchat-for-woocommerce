<?php
/**
 * Unit tests for the OptionDefaults utility class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Utils
 */

namespace SnapchatForWooCommerce\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\OptionDefaults
 */
class OptionDefaultsTest extends TestCase {

	/**
	 * Ensure all defined keys exist in the default map.
	 */
	public function test_all_keys_are_mapped_in_defaults(): void {
		$defaults = OptionDefaults::get_all();

		$this->assertArrayHasKey( OptionDefaults::AD_ACCOUNT_ID, $defaults );
		$this->assertArrayHasKey( OptionDefaults::ORGANIZATION_ID, $defaults );
		$this->assertArrayHasKey( OptionDefaults::PIXEL_ENABLED, $defaults );
	}

	/**
	 * Ensure default values are of expected type and match expectations.
	 */
	public function test_default_values_are_correct(): void {
		$defaults = OptionDefaults::get_all();

		$this->assertSame( '', $defaults[ OptionDefaults::AD_ACCOUNT_ID ] );
		$this->assertSame( '', $defaults[ OptionDefaults::ORGANIZATION_ID ] );
		$this->assertFalse( $defaults[ OptionDefaults::PIXEL_ENABLED ] );
	}

	/**
	 * Ensure the method always returns an array.
	 */
	public function test_get_all_returns_array(): void {
		$this->assertIsArray( OptionDefaults::get_all() );
	}
}
