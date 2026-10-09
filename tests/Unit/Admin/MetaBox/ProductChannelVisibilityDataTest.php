<?php
/**
 * Unit tests for the ProductChannelVisibilityData class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use WC_Product_Simple;
use SnapchatForWooCommerce\Admin\MetaBox\ProductChannelVisibilityData;
use SnapchatForWooCommerce\Admin\ProductMeta\ProductMetaFields;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\Admin\MetaBox\ProductChannelVisibilityData
 */
final class ProductChannelVisibilityDataTest extends WP_UnitTestCase {

	/**
	 * Meta key backing the catalog-item control.
	 *
	 * @var string
	 */
	private $field_name;

	/**
	 * Sets up the test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		if ( ! function_exists( 'set_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		$this->field_name = Helper::with_prefix( ProductMetaFields::CATALOG_ITEM );
	}

	/**
	 * Tears down the test environment.
	 */
	public function tear_down(): void {
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * Off the product edit screen there is no payload and the bundle is not enqueued.
	 */
	public function test_returns_null_off_product_screen(): void {
		set_current_screen( 'dashboard' );

		$this->assertNull( ProductChannelVisibilityData::get_channel_visibility_inline_block() );
		$this->assertFalse( ProductChannelVisibilityData::should_enqueue_channel_visibility_bundle() );
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
	 * Tests the payload shape and defaults for a visible product with no saved meta.
	 */
	public function test_builds_payload_with_defaults_for_visible_product(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Example product' );
		$product->set_status( 'publish' );
		$product->save();

		$this->enter_product_edit_screen( $product->get_id() );

		$block = ProductChannelVisibilityData::get_channel_visibility_inline_block();

		$this->assertIsArray( $block );
		$this->assertSame( $this->field_name, $block['field_name'] );
		$this->assertSame( '1', $block['product_catalog_item'] );
		$this->assertTrue( $block['product_is_visible'] );
		$this->assertSame(
			array(
				array(
					'value' => '1',
					'label' => 'Sync and show',
				),
				array(
					'value' => '0',
					'label' => "Don't sync and show",
				),
			),
			$block['options']
		);

		$this->assertTrue( ProductChannelVisibilityData::should_enqueue_channel_visibility_bundle() );
	}

	/**
	 * Tests that a saved '0' meta value is reflected in the payload.
	 */
	public function test_reflects_saved_catalog_item_value(): void {
		$product = new WC_Product_Simple();
		$product->set_status( 'publish' );
		$product->save();

		update_post_meta( $product->get_id(), $this->field_name, '0' );

		$this->enter_product_edit_screen( $product->get_id() );

		$block = ProductChannelVisibilityData::get_channel_visibility_inline_block();

		$this->assertSame( '0', $block['product_catalog_item'] );
	}

	/**
	 * On a product edit screen the payload reports onboarding incomplete by default.
	 */
	public function test_payload_reports_onboarding_incomplete(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'incomplete' );

		$product = new WC_Product_Simple();
		$product->set_status( 'publish' );
		$product->save();

		$this->enter_product_edit_screen( $product->get_id() );

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

		$product = new WC_Product_Simple();
		$product->set_status( 'publish' );
		$product->save();

		$this->enter_product_edit_screen( $product->get_id() );

		$payload = ProductChannelVisibilityData::get_channel_visibility_inline_block();

		$this->assertIsArray( $payload );
		$this->assertTrue( $payload['onboardingComplete'] );
	}

	/**
	 * Puts the request into a product edit context for the given product.
	 *
	 * @param int $product_id The product post ID.
	 * @return void
	 */
	private function enter_product_edit_screen( int $product_id ): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['post'] = get_post( $product_id );
		set_current_screen( 'product' );
	}
}
