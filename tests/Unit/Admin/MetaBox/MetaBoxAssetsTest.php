<?php
/**
 * Unit tests for the MetaBoxAssets class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Admin\MetaBox\MetaBoxAssets;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\Admin\MetaBox\MetaBoxAssets
 */
final class MetaBoxAssetsTest extends WP_UnitTestCase {

	private const SCRIPT_BASENAME = 'channel-visibility-meta-box';

	public function set_up(): void {
		parent::set_up();

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		if ( ! function_exists( 'set_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		wp_mkdir_p( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH );

		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_BASENAME . '.js', '// test script' );
		file_put_contents(
			SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_BASENAME . '.asset.php',
			'<?php return [ "dependencies" => [], "version" => "1.0.0" ];'
		);

		$this->purge_script_registration();
	}

	public function tear_down(): void {
		$this->purge_script_registration();
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_BASENAME . '.js' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_BASENAME . '.asset.php' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * The bundle is not enqueued away from the product edit screen.
	 */
	public function test_bundle_not_enqueued_off_product_screen(): void {
		set_current_screen( 'dashboard' );

		( new MetaBoxAssets() )->enqueue_assets();

		$this->assertFalse(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_BASENAME, 'enqueued' )
		);
	}

	/**
	 * The bundle is enqueued on the product edit screen and carries the onboarding state.
	 */
	public function test_bundle_enqueued_on_product_screen_with_onboarding_state(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'incomplete' );
		$this->place_product_on_edit_screen();

		( new MetaBoxAssets() )->enqueue_assets();

		$this->assertTrue(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_BASENAME, 'enqueued' )
		);

		$payload = $this->decode_metabox_payload();
		$this->assertArrayHasKey( 'onboardingComplete', $payload );
		$this->assert_payload_bool( $payload['onboardingComplete'], false );
	}

	/**
	 * A connected setup reports onboarding complete in the localized payload.
	 */
	public function test_payload_reports_onboarding_complete_when_connected(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );
		$this->place_product_on_edit_screen();

		( new MetaBoxAssets() )->enqueue_assets();

		$payload = $this->decode_metabox_payload();
		$this->assert_payload_bool( $payload['onboardingComplete'], true );
	}

	/**
	 * Sets the current screen to the product editor with a product as the global post.
	 */
	private function place_product_on_edit_screen(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$post    = get_post( $product->get_id() );
		$this->assertNotNull( $post );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['post'] = $post;
		set_current_screen( 'product' );
	}

	/**
	 * Deregisters the bundle so each test observes a clean enqueue.
	 */
	private function purge_script_registration(): void {
		$handle = Config::ASSET_HANDLE_PREFIX . self::SCRIPT_BASENAME;
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}

	/**
	 * Decodes the `snapchatAdsMetaBoxData` object localized on the bundle handle.
	 *
	 * @return array<string,mixed>
	 */
	private function decode_metabox_payload(): array {
		global $wp_scripts;

		$handle     = Config::ASSET_HANDLE_PREFIX . self::SCRIPT_BASENAME;
		$registered = $wp_scripts->registered[ $handle ] ?? null;
		$this->assertNotNull( $registered );

		$data = isset( $registered->extra['data'] ) ? (string) $registered->extra['data'] : '';
		$var  = Config::AD_PARTNER_JS_VAR_PREFIX . 'MetaBoxData';

		$var_at = strpos( $data, 'var ' . $var );
		$this->assertNotFalse( $var_at );

		$brace_left = strpos( $data, '{', $var_at );
		$this->assertNotFalse( $brace_left );

		$depth = 0;
		$end   = $brace_left;
		for ( $i = $brace_left, $len = strlen( $data ); $i < $len; $i++ ) {
			if ( '{' === $data[ $i ] ) {
				$depth++;
			} elseif ( '}' === $data[ $i ] ) {
				$depth--;
				if ( 0 === $depth ) {
					$end = $i;
					break;
				}
			}
		}

		$decoded = json_decode( substr( $data, $brace_left, $end - $brace_left + 1 ), true );
		$this->assertIsArray( $decoded );

		return $decoded;
	}

	/**
	 * wp_localize_script stringifies scalars, so booleans arrive as "1"/"". Compare loosely.
	 *
	 * @param mixed $value    Decoded value.
	 * @param bool  $expected Expected truthiness.
	 * @return void
	 */
	private function assert_payload_bool( $value, bool $expected ): void {
		$actual = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		if ( null === $actual ) {
			$actual = (bool) $value;
		}
		$this->assertSame( $expected, $actual );
	}
}
