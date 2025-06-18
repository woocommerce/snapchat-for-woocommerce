<?php
/**
 * Integration tests for the OptionStorage class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Utils
 */

namespace SnapchatForWooCommerce\Tests\Integration\Utils;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\OptionStorage;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\OptionStorage
 */
class OptionStorageTest extends WP_UnitTestCase {

	private OptionStorage $storage;

	protected function setUp(): void {
		parent::setUp();
		$this->storage = new OptionStorage();
		delete_option( Config::STORE_PREFIX . 'test_key' );
	}

	protected function tearDown(): void {
		delete_option( Config::STORE_PREFIX . 'test_key' );
		parent::tearDown();
	}

	public function test_get_returns_false_when_option_missing(): void {
		$this->assertFalse(
			$this->storage->get( 'test_key' )
		);
	}

	public function test_set_saves_value_to_wp_options(): void {
		$this->assertTrue(
			$this->storage->set( 'test_key', 'example_value' )
		);

		$this->assertSame(
			'example_value',
			get_option( Config::STORE_PREFIX . 'test_key' )
		);
	}

	public function test_get_returns_stored_value(): void {
		update_option( Config::STORE_PREFIX . 'test_key', 'stored_value' );

		$this->assertSame(
			'stored_value',
			$this->storage->get( 'test_key' )
		);
	}

	public function test_delete_removes_option(): void {
		update_option( Config::STORE_PREFIX . 'test_key', 'value_to_delete' );

		$this->assertTrue(
			$this->storage->delete( 'test_key' )
		);

		$this->assertFalse(
			get_option( Config::STORE_PREFIX . 'test_key' )
		);
	}
}
