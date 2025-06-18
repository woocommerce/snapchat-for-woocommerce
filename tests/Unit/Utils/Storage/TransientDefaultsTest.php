<?php
/**
 * Unit tests for the TransientDefaults utility class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Utils
 */

namespace SnapchatForWooCommerce\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\TransientDefaults
 */
class TransientDefaultsTest extends TestCase {

	public function test_get_all_returns_expected_keys(): void {
		$all = TransientDefaults::get_all();

		$this->assertArrayHasKey( TransientDefaults::PIXEL_SCRIPT, $all );
		$this->assertSame( MONTH_IN_SECONDS, $all[ TransientDefaults::PIXEL_SCRIPT ] );
	}

	public function test_get_all_returns_array(): void {
		$this->assertIsArray( TransientDefaults::get_all() );
	}

	public function test_get_ttl_returns_value_if_key_exists(): void {
		$ttl = TransientDefaults::get_ttl( TransientDefaults::PIXEL_SCRIPT );

		$this->assertSame( MONTH_IN_SECONDS, $ttl );
	}

	public function test_get_ttl_returns_fallback_if_key_missing(): void {
		$ttl = TransientDefaults::get_ttl( 'nonexistent_key' );

		$this->assertSame( HOUR_IN_SECONDS, $ttl );
	}
}
