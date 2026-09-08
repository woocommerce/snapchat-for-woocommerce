<?php
/**
 * Unit tests for the ProductChannelVisibilityData class.
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use WC_Product_Simple;
use SnapchatForWooCommerce\Admin\MetaBox\ProductChannelVisibilityData;
use SnapchatForWooCommerce\Admin\ProductMeta\ProductMetaFields;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * @covers \SnapchatForWooCommerce\Admin\MetaBox\ProductChannelVisibilityData
 */
class ProductChannelVisibilityDataTest extends WP_UnitTestCase {

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
	 * Puts the request into a product edit context for the given product.
	 *
	 * @param int $product_id The product post ID.
	 * @return void
	 */
	private function enter_product_edit_screen( int $product_id ): void {
		set_current_screen( 'product' );
		$GLOBALS['post'] = get_post( $product_id );
	}

	/**
	 * Tests that no payload is built outside a product edit screen.
	 */
	public function test_returns_null_without_product_screen() {
		$this->assertNull( ProductChannelVisibilityData::get_channel_visibility_inline_block() );
		$this->assertFalse( ProductChannelVisibilityData::should_enqueue_channel_visibility_bundle() );
	}

	/**
	 * Tests the payload shape and defaults for a visible product with no saved meta.
	 */
	public function test_builds_payload_with_defaults_for_visible_product() {
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
	public function test_reflects_saved_catalog_item_value() {
		$product = new WC_Product_Simple();
		$product->set_status( 'publish' );
		$product->save();

		update_post_meta( $product->get_id(), $this->field_name, '0' );

		$this->enter_product_edit_screen( $product->get_id() );

		$block = ProductChannelVisibilityData::get_channel_visibility_inline_block();

		$this->assertSame( '0', $block['product_catalog_item'] );
	}
}
