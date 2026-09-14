<?php
/**
 * Unit test for MetaBoxAssets class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit
 */

namespace SnapchatForWooCommerce\Tests\Unit;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Admin\MetaBox\MetaBoxAssets;
use SnapchatForWooCommerce\Admin\MetaBox\OrderAttributionData;

/**
 * @covers \SnapchatForWooCommerce\Admin\MetaBox\MetaBoxAssets
 */
final class MetaBoxAssetsTest extends WP_UnitTestCase {

	private const HANDLE = Config::ASSET_HANDLE_PREFIX . 'order-attribution';

	public function set_up(): void {
		parent::set_up();

		wp_mkdir_p( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH );

		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'order-attribution', '// fallback for filemtime' );
		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'order-attribution.js', '// dummy script' );
		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'order-attribution.css', '/* dummy style */' );
		file_put_contents(
			SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'order-attribution.js.asset.php',
			'<?php return [ "dependencies" => [], "version" => "1.0.0" ];'
		);
	}

	public function tear_down(): void {
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'order-attribution' );
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'order-attribution.js' );
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'order-attribution.css' );
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'order-attribution.js.asset.php' );

		wp_deregister_script( self::HANDLE );
		wp_deregister_style( self::HANDLE );

		parent::tear_down();
	}

	public function test_does_not_enqueue_when_not_order_edit_screen(): void {
		$data = $this->createMock( OrderAttributionData::class );
		$data->method( 'is_wc_order_edit_screen' )->willReturn( false );

		$assets = new MetaBoxAssets( $data );
		$assets->enqueue_assets();

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	public function test_enqueues_and_localizes_on_order_edit_screen(): void {
		$data = $this->createMock( OrderAttributionData::class );
		$data->method( 'is_wc_order_edit_screen' )->willReturn( true );
		$data->method( 'get_order_attribution_source_for_edit_screen' )->willReturn( 'snapchat' );

		$assets = new MetaBoxAssets( $data );
		$assets->enqueue_assets();

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( self::HANDLE, 'enqueued' ) );

		$inline = wp_scripts()->get_data( self::HANDLE, 'data' );

		$this->assertStringContainsString( 'snapchatAdsMetaBoxData', $inline );
		$this->assertStringContainsString( 'orderAttributionSource', $inline );
		$this->assertStringContainsString( 'snapchat', $inline );
		$this->assertStringContainsString( 'snapchatAdsAdminData', $inline );
	}
}
