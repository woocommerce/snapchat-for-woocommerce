<?php
/**
 * Unit tests for the Store class using a mocked strategy.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Utils
 */

namespace SnapchatForWooCommerce\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use SnapchatForWooCommerce\Utils\Storage\Store;
use SnapchatForWooCommerce\Utils\Storage\StorageStrategy;

/**
 * @covers \SnapchatForWooCommerce\Utils\Storage\Store
 */
class StoreTest extends TestCase {

	private $mock_strategy;
	private Store $store;

	protected function setUp(): void {
		parent::setUp();

		$this->mock_strategy = $this->createMock( StorageStrategy::class );

		$this->store = new Store(
			$this->mock_strategy,
			[
				'pixel_enabled' => true,
				'pixel_script'  => '<script>fallback()</script>',
			]
		);
	}

	public function test_get_returns_value_from_strategy_if_found(): void {
		$this->mock_strategy
			->method( 'get' )
			->with( 'pixel_enabled' )
			->willReturn( false );

		$this->assertTrue( $this->store->get( 'pixel_enabled' ) );
	}

	public function test_get_returns_default_if_strategy_returns_false(): void {
		$this->mock_strategy
			->expects( $this->once() )
			->method( 'get' )
			->with( 'pixel_script' )
			->willReturn( false );

		$this->assertSame(
			'<script>fallback()</script>',
			$this->store->get( 'pixel_script' )
		);
	}

	public function test_get_returns_strategy_value_if_non_false(): void {
		$this->mock_strategy
			->expects( $this->once() )
			->method( 'get' )
			->with( 'pixel_script' )
			->willReturn( '<script>real()</script>' );

		$this->assertSame(
			'<script>real()</script>',
			$this->store->get( 'pixel_script' )
		);
	}

	public function test_set_delegates_to_strategy(): void {
		$this->mock_strategy
			->expects( $this->once() )
			->method( 'set' )
			->with( 'pixel_enabled', false )
			->willReturn( true );

		$this->assertTrue(
			$this->store->set( 'pixel_enabled', false )
		);
	}

	public function test_delete_delegates_to_strategy(): void {
		$this->mock_strategy
			->expects( $this->once() )
			->method( 'delete' )
			->with( 'pixel_script' )
			->willReturn( true );

		$this->assertTrue(
			$this->store->delete( 'pixel_script' )
		);
	}
}
