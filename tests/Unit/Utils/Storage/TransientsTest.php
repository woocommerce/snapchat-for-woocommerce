<?php
/**
 * Integration tests for the Transients utility class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Utils
 */

namespace SnapchatForWooCommerce\Tests\Integration\Utils;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\Transients
 */
class TransientsTest extends WP_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->delete_all_transient_keys();
	}

	protected function tearDown(): void {
		$this->delete_all_transient_keys();
		parent::tearDown();
	}

	private function delete_all_transient_keys(): void {
		foreach ( TransientDefaults::get_all() as $key => $_ ) {
			delete_transient( Config::STORE_PREFIX . $key );
		}
	}

	public function test_get_returns_existing_transient(): void {
		set_transient( Config::STORE_PREFIX . TransientDefaults::PIXEL_SCRIPT, '<script>real()</script>', HOUR_IN_SECONDS );

		$this->assertSame(
			'<script>real()</script>',
			Transients::get( TransientDefaults::PIXEL_SCRIPT )
		);
	}

	public function test_get_returns_default_when_transient_missing(): void {
		delete_transient( Config::STORE_PREFIX . TransientDefaults::PIXEL_SCRIPT );

		$this->assertSame(
			TransientDefaults::get_all()[ TransientDefaults::PIXEL_SCRIPT ],
			Transients::get( TransientDefaults::PIXEL_SCRIPT )
		);
	}

	public function test_set_stores_value_in_transient(): void {
		Transients::set( TransientDefaults::PIXEL_SCRIPT, '<script>new()</script>' );

		$this->assertSame(
			'<script>new()</script>',
			get_transient( Config::STORE_PREFIX . TransientDefaults::PIXEL_SCRIPT )
		);
	}

	public function test_delete_removes_transient(): void {
		set_transient( Config::STORE_PREFIX . TransientDefaults::PIXEL_SCRIPT, 'to_delete', HOUR_IN_SECONDS );

		Transients::delete( TransientDefaults::PIXEL_SCRIPT );

		$this->assertFalse(
			get_transient( Config::STORE_PREFIX . TransientDefaults::PIXEL_SCRIPT )
		);
	}
}
