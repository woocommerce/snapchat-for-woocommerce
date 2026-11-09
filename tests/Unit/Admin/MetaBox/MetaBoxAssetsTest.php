<?php
/**
 * Unit tests for the MetaBoxAssets class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Admin\MetaBox\MetaBoxAssets;
use SnapchatForWooCommerce\Admin\MetaBox\OrderAttributionData;
use SnapchatForWooCommerce\Campaign\CampaignStatusService;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\Admin\MetaBox\MetaBoxAssets
 */
final class MetaBoxAssetsTest extends WP_UnitTestCase {

	private const SCRIPT_BASENAME = 'channel-visibility-meta-box';

	private const ORDER_SCRIPT_BASENAME = 'order-attribution';

	private const ORDER_HANDLE = Config::ASSET_HANDLE_PREFIX . self::ORDER_SCRIPT_BASENAME;

	public function set_up(): void {
		parent::set_up();

		if ( ! function_exists( 'set_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		wp_mkdir_p( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH );

		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_BASENAME . '.js', '// test script' );
		file_put_contents(
			SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_BASENAME . '.asset.php',
			'<?php return [ "dependencies" => [], "version" => "1.0.0" ];'
		);

		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::ORDER_SCRIPT_BASENAME, '// fallback for filemtime' );
		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::ORDER_SCRIPT_BASENAME . '.js', '// dummy script' );
		file_put_contents( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::ORDER_SCRIPT_BASENAME . '.css', '/* dummy style */' );
		file_put_contents(
			SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::ORDER_SCRIPT_BASENAME . '.js.asset.php',
			'<?php return [ "dependencies" => [], "version" => "1.0.0" ];'
		);

		$this->purge_script_registration();
	}

	public function tear_down(): void {
		$this->purge_script_registration();
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_BASENAME . '.js' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_BASENAME . '.asset.php' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::ORDER_SCRIPT_BASENAME ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::ORDER_SCRIPT_BASENAME . '.js' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::ORDER_SCRIPT_BASENAME . '.css' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::ORDER_SCRIPT_BASENAME . '.js.asset.php' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		unset( $GLOBALS['post'] );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * The bundle is not enqueued away from the product edit screen.
	 */
	public function test_bundle_not_enqueued_off_product_screen(): void {
		set_current_screen( 'dashboard' );

		( new MetaBoxAssets( new OrderAttributionData(), $this->make_campaign_status_mock() ) )->enqueue_assets();

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

		( new MetaBoxAssets( new OrderAttributionData(), $this->make_campaign_status_mock() ) )->enqueue_assets();

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

		( new MetaBoxAssets( new OrderAttributionData(), $this->make_campaign_status_mock() ) )->enqueue_assets();

		$payload = $this->decode_metabox_payload();
		$this->assert_payload_bool( $payload['onboardingComplete'], true );
	}

	/**
	 * The order bundle is not enqueued away from the order edit screen.
	 */
	public function test_order_bundle_not_enqueued_off_order_edit_screen(): void {
		$data = $this->createMock( OrderAttributionData::class );
		$data->method( 'is_wc_order_edit_screen' )->willReturn( false );

		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->expects( $this->never() )->method( 'has_campaigns' );

		( new MetaBoxAssets( $data, $campaign_status ) )->enqueue_assets();

		$this->assertFalse( wp_script_is( self::ORDER_HANDLE, 'enqueued' ) );
	}

	/**
	 * The order bundle is not enqueued for an order that Snapchat did not refer.
	 */
	public function test_order_bundle_not_enqueued_when_order_not_snapchat_attributed(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );

		$data = $this->createMock( OrderAttributionData::class );
		$data->method( 'is_wc_order_edit_screen' )->willReturn( true );
		$data->method( 'get_order_attribution_source_for_edit_screen' )->willReturn( null );

		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->expects( $this->never() )->method( 'has_campaigns' );

		( new MetaBoxAssets( $data, $campaign_status ) )->enqueue_assets();

		$this->assertFalse( wp_script_is( self::ORDER_HANDLE, 'enqueued' ) );
		$this->assertFalse( wp_style_is( self::ORDER_HANDLE, 'enqueued' ) );
	}

	/**
	 * The campaign check is skipped while onboarding is incomplete.
	 */
	public function test_order_bundle_skips_campaign_check_when_onboarding_incomplete(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'incomplete' );

		$data = $this->createMock( OrderAttributionData::class );
		$data->method( 'is_wc_order_edit_screen' )->willReturn( true );
		$data->method( 'get_order_attribution_source_for_edit_screen' )->willReturn( 'snapchat' );

		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->expects( $this->never() )->method( 'has_campaigns' );

		( new MetaBoxAssets( $data, $campaign_status ) )->enqueue_assets();

		$this->assertTrue( wp_script_is( self::ORDER_HANDLE, 'enqueued' ) );
	}

	/**
	 * The order bundle is enqueued and localized for a Snapchat-attributed order.
	 */
	public function test_order_bundle_enqueued_and_localized_for_snapchat_order(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );

		$data = $this->createMock( OrderAttributionData::class );
		$data->method( 'is_wc_order_edit_screen' )->willReturn( true );
		$data->method( 'get_order_attribution_source_for_edit_screen' )->willReturn( 'snapchat' );

		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->expects( $this->once() )->method( 'has_campaigns' )->willReturn( true );

		( new MetaBoxAssets( $data, $campaign_status ) )->enqueue_assets();

		$this->assertTrue( wp_script_is( self::ORDER_HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( self::ORDER_HANDLE, 'enqueued' ) );

		$inline = wp_scripts()->get_data( self::ORDER_HANDLE, 'data' );

		$this->assertStringContainsString( 'snapchatAdsMetaBoxData', $inline );
		$this->assertStringContainsString( 'orderAttributionSource', $inline );
		$this->assertStringContainsString( 'snapchat', $inline );
		$this->assertStringContainsString( 'snapchatAdsAdminData', $inline );
		$this->assertStringContainsString( 'hasCampaign', $inline );
	}

	/**
	 * Builds a stub `CampaignStatusService` so these tests don't hit the live Ad Partner endpoint.
	 *
	 * @param bool $has_campaign Value `has_campaigns()` should return.
	 * @return CampaignStatusService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function make_campaign_status_mock( bool $has_campaign = false ) {
		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->method( 'has_campaigns' )->willReturn( $has_campaign );

		return $campaign_status;
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
	 * Deregisters both bundles so each test observes a clean enqueue.
	 */
	private function purge_script_registration(): void {
		$handle = Config::ASSET_HANDLE_PREFIX . self::SCRIPT_BASENAME;
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );

		wp_dequeue_script( self::ORDER_HANDLE );
		wp_deregister_script( self::ORDER_HANDLE );
		wp_dequeue_style( self::ORDER_HANDLE );
		wp_deregister_style( self::ORDER_HANDLE );
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
