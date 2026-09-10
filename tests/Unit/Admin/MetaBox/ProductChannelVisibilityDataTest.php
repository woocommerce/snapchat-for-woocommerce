<?php
/**
 * Unit tests for the ProductChannelVisibilityData class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Admin\MetaBox\ProductChannelVisibilityData;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\Admin\MetaBox\ProductChannelVisibilityData
 */
final class ProductChannelVisibilityDataTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		if ( ! function_exists( 'set_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}
	}

	public function tear_down(): void {
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * Off the product edit screen there is no payload.
	 */
	public function test_returns_null_off_product_screen(): void {
		set_current_screen( 'dashboard' );

		$this->assertNull( ProductChannelVisibilityData::get_channel_visibility_inline_block() );
	}

	/**
	 * On the product screen without a global product post there is no payload.
	 */
	public function test_returns_null_on_product_screen_without_global_post(): void {
		unset( $GLOBALS['post'] );
		set_current_screen( 'product' );

		$this->assertNull( ProductChannelVisibilityData::get_channel_visibility_inline_block() );
	}

	/**
	 * On a product edit screen the payload reports onboarding incomplete by default.
	 */
	public function test_payload_reports_onboarding_incomplete(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'incomplete' );
		$this->place_product_on_edit_screen();

		$payload = ProductChannelVisibilityData::get_channel_visibility_inline_block();

		$this->assertIsArray( $payload );
		$this->assertArrayHasKey( 'onboardingComplete', $payload );
		$this->assertFalse( $payload['onboardingComplete'] );
	}

	/**
	 * A connected setup reports onboarding complete.
	 */
	public function test_payload_reports_onboarding_complete_when_connected(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );
		$this->place_product_on_edit_screen();

		$payload = ProductChannelVisibilityData::get_channel_visibility_inline_block();

		$this->assertIsArray( $payload );
		$this->assertTrue( $payload['onboardingComplete'] );
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
}
