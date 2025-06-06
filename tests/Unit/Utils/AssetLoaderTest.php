<?php
/**
 * Integration tests for the AssetLoader utility class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Utils
 */

namespace SnapchatForWooCommerce\Tests\Integration\Utils;

use SnapchatForWooCommerce\Utils\AssetLoader;
use SnapchatForWooCommerce\Config;
use WP_UnitTestCase;

class AssetLoaderTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		// Ensure build directory exists inside the plugin.
		wp_mkdir_p( SNAPCHAT_ADS_PLUGIN_BUILD_PATH . 'js' );
		wp_mkdir_p( SNAPCHAT_ADS_PLUGIN_BUILD_PATH . 'css' );
	}

	public function tear_down(): void {
		@unlink( SNAPCHAT_ADS_PLUGIN_BUILD_PATH . 'js/test-script.js' );
		@unlink( SNAPCHAT_ADS_PLUGIN_BUILD_PATH . 'js/test-script.js.asset.php' );
		@unlink( SNAPCHAT_ADS_PLUGIN_BUILD_PATH . 'css/test-style.css' );
		@unlink( SNAPCHAT_ADS_PLUGIN_BUILD_PATH . 'js/localize.js' );
		@unlink( SNAPCHAT_ADS_PLUGIN_BUILD_PATH . 'js/localize.js.asset.php' );

		parent::tear_down();
	}

	/**
	 * Tests that a JavaScript file with an accompanying `.asset.php` file
	 * is correctly enqueued by `AssetLoader::enqueue_script()`.
	 *
	 * Asserts:
	 * - The script is registered in WordPress.
	 * - The script URL is correctly built.
	 * - The dependencies and version from the `.asset.php` file are respected.
	 */
	public function test_enqueue_script_with_asset_php() {
		$handle      = 'test-script';
		$script_name = 'js/test-script.js';

		$script_path       = SNAPCHAT_ADS_PLUGIN_BUILD_PATH . $script_name;
		$script_asset_path = $script_path . '.asset.php';

		file_put_contents( $script_path, '// js file' );
		file_put_contents( $script_asset_path, '<?php return [ "dependencies" => ["jquery"], "version" => "1.2.3" ];' );

		AssetLoader::enqueue_script( $handle, $script_name );

		$registered = wp_scripts()->registered[ Config::ASSET_HANDLE_PREFIX . $handle ];

		$this->assertNotNull( $registered );
		$this->assertSame(
			SNAPCHAT_ADS_PLUGIN_BUILD_URL . $script_name . '.js',
			$registered->src
		);
		$this->assertContains( 'jquery', $registered->deps );
		$this->assertSame( '1.2.3', $registered->ver );
	}

	/**
	 * Tests that a CSS file without a `.asset.php` file is enqueued correctly
	 * using the file modification time (`filemtime`) as the version.
	 *
	 * Asserts:
	 * - The style is registered in WordPress.
	 * - The `version` is set to the `filemtime()` value.
	 */
	public function test_enqueue_style_falls_back_to_filemtime() {
		$handle     = 'test-style';
		$style_name = 'css/test-style.css';
		$style_path = SNAPCHAT_ADS_PLUGIN_BUILD_PATH . $style_name;

		file_put_contents( $style_path, 'body { color: red; }' );
		$mtime = filemtime( $style_path );

		AssetLoader::enqueue_style( $handle, $style_name );

		$registered = wp_styles()->registered[ Config::ASSET_HANDLE_PREFIX . $handle ];

		$this->assertNotNull( $registered );
		$this->assertSame( $mtime, $registered->ver );
	}

	/**
	 * Tests that localizing data with `AssetLoader::localize_script()`
	 * correctly attaches the data to the script tag.
	 *
	 * Asserts:
	 * - The script has a `data` entry in the `extra` array.
	 * - The localized JavaScript object is present in the `data` string.
	 * - The object contains the expected key-value pairs.
	 */
	public function test_localize_script_adds_data_to_script_tag() {
		$handle      = 'localize';
		$script_name = 'js/localize.js';

		file_put_contents( SNAPCHAT_ADS_PLUGIN_BUILD_PATH . $script_name, '// js' );
		file_put_contents(
			SNAPCHAT_ADS_PLUGIN_BUILD_PATH . $script_name . '.asset.php',
			'<?php return [ "dependencies" => [], "version" => "1.0.0" ];'
		);

		AssetLoader::enqueue_script( $handle, $script_name );

		$data = array( 'foo' => 'bar' );
		AssetLoader::localize_script( $handle, 'MyObject', $data );

		$registered = wp_scripts()->registered[ Config::ASSET_HANDLE_PREFIX . $handle ];

		$this->assertArrayHasKey( 'data', $registered->extra );
		$this->assertStringContainsString( 'var MyObject =', $registered->extra['data'] );
		$this->assertStringContainsString( '"foo":"bar"', $registered->extra['data'] );
	}
}
