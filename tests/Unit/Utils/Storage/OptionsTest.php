<?php
/**
 * Integration tests for the Options utility class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Utils
 */

namespace SnapchatForWooCommerce\Tests\Integration\Utils;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Options;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\Options
 */
class OptionsTest extends WP_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->delete_all_option_keys();
	}

	protected function tearDown(): void {
		$this->delete_all_option_keys();
		parent::tearDown();
	}

	private function delete_all_option_keys(): void {
		foreach ( OptionDefaults::get_all() as $key => $_ ) {
			delete_option( Config::STORE_PREFIX . $key );
		}
	}

	public function test_get_returns_existing_option(): void {
		update_option( Config::STORE_PREFIX . OptionDefaults::PIXEL_ENABLED, true );

		$this->assertTrue(
			Options::get( OptionDefaults::PIXEL_ENABLED )
		);
	}

	public function test_get_returns_default_if_option_missing(): void {
		$this->assertTrue(
			Options::get( OptionDefaults::PIXEL_ENABLED )
		);
	}

	public function test_set_persists_option(): void {
		Options::set( OptionDefaults::PIXEL_ENABLED, true );

		$this->assertTrue(
			get_option( Config::STORE_PREFIX . OptionDefaults::PIXEL_ENABLED )
		);

		Options::set( OptionDefaults::PIXEL_ENABLED, false );

		$this->assertFalse(
			get_option( Config::STORE_PREFIX . OptionDefaults::PIXEL_ENABLED )
		);
	}

	public function test_delete_removes_option(): void {
		update_option( Config::STORE_PREFIX . OptionDefaults::PIXEL_ENABLED, true );

		Options::delete( OptionDefaults::PIXEL_ENABLED );

		$this->assertFalse(
			get_option( Config::STORE_PREFIX . OptionDefaults::PIXEL_ENABLED )
		);
	}

	public function test_preload_defaults_sets_only_missing_keys(): void {
		// Pre-set one of the options to a non-default value.
		update_option( Config::STORE_PREFIX . OptionDefaults::PIXEL_ENABLED, 'custom_value' );

		Options::preload_defaults();
		$defaults = OptionDefaults::get_all();

		foreach ( $defaults as $key => $expected ) {
			$actual = get_option( Config::STORE_PREFIX . $key );

			if ( $key === OptionDefaults::PIXEL_ENABLED ) {
				$this->assertSame( 'custom_value', $actual );
			} else {
				$this->assertSame( $expected, $actual );
			}
		}
	}
}
