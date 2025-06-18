<?php
/**
 * Integration tests for the TransientStorage class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Utils
 */

namespace SnapchatForWooCommerce\Tests\Integration\Utils;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\TransientStorage;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\TransientStorage
 */
class TransientStorageTest extends WP_UnitTestCase {

	private TransientStorage $storage;
	private string $key;

	protected function setUp(): void {
		parent::setUp();
		$this->storage = new TransientStorage();
		$this->key     = TransientDefaults::PIXEL_SCRIPT;

		delete_transient( Config::STORE_PREFIX . $this->key );
	}

	protected function tearDown(): void {
		delete_transient( Config::STORE_PREFIX . $this->key );
		parent::tearDown();
	}

	public function test_get_returns_false_if_transient_missing(): void {
		$this->assertFalse(
			$this->storage->get( $this->key )
		);
	}

	public function test_set_stores_value_with_ttl(): void {
		$result = $this->storage->set( $this->key, '<script>cached()</script>' );

		$this->assertTrue( $result );

		$this->assertSame(
			'<script>cached()</script>',
			get_transient( Config::STORE_PREFIX . $this->key )
		);
	}

	public function test_get_returns_stored_value(): void {
		set_transient( Config::STORE_PREFIX . $this->key, 'preset', HOUR_IN_SECONDS );

		$this->assertSame(
			'preset',
			$this->storage->get( $this->key )
		);
	}

	public function test_delete_removes_transient(): void {
		set_transient( Config::STORE_PREFIX . $this->key, 'to_delete', HOUR_IN_SECONDS );

		$this->assertTrue(
			$this->storage->delete( $this->key )
		);

		$this->assertFalse(
			get_transient( Config::STORE_PREFIX . $this->key )
		);
	}
}
