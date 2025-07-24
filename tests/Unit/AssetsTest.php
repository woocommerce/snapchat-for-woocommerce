<?php
/**
 * Unit test for Assets class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit
 */

namespace SnapchatForWooCommerce\Tests\Unit;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Assets;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\Assets
 */
final class AssetsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		wp_mkdir_p( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH );

		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'tracking', '// fallback for filemtime' );
		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'tracking.js', '// dummy script' );
		file_put_contents(
			SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'tracking.js.asset.php',
			'<?php return [ "dependencies" => [], "version" => "1.0.0" ];'
		);

		wp_deregister_script( Config::ASSET_HANDLE_PREFIX . 'tracking' );
	}


	public function tear_down(): void {
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'tracking' );
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'tracking.js' );
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . 'tracking.js.asset.php' );
		parent::tear_down();
	}

	public function test_scripts_not_enqueued_when_tracking_disabled(): void {
		Options::set( OptionDefaults::PIXEL_ENABLED, 'no' );
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'no' );

		$assets = new Assets();
		$assets->enqueue_scripts();

		$this->assertFalse(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . 'tracking', 'enqueued' )
		);
	}

	public function test_scripts_enqueued_when_pixel_enabled(): void {
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'no' );

		$assets = new Assets();
		$assets->enqueue_scripts();

		$this->assertTrue(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . 'tracking', 'enqueued' )
		);
	}

	public function test_scripts_enqueued_when_conversion_enabled(): void {
		Options::set( OptionDefaults::PIXEL_ENABLED, 'no' );
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );

		$assets = new Assets();
		$assets->enqueue_scripts();

		$this->assertTrue(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . 'tracking', 'enqueued' )
		);
	}

	public function test_localized_data_passed_correctly(): void {
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );

		$assets = new Assets();

		add_filter(
			Helper::with_prefix( 'filter_tracking_data' ),
			function ( $data ) {
				$this->assertTrue( $data['is_pixel_enabled'] );
				$this->assertTrue( $data['is_conversion_enabled'] );
				$this->assertIsString( $data['capi_nonce'] );
				return $data;
			}
		);

		$assets->enqueue_scripts();
	}
}
