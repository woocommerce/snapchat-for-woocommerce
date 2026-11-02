<?php
/**
 * Unit tests for the ProductIdCacheBuilder class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Admin\Export\Service
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\Export\Service;

use WP_UnitTestCase;
use WC_Product_Simple;
use SnapchatForWooCommerce\Admin\Export\Service\ProductIdCacheBuilder;
use SnapchatForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\Admin\Export\Service\ProductIdCacheBuilder
 */
class ProductIdCacheBuilderTest extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var ProductIdCacheBuilder
	 */
	private ProductIdCacheBuilder $sut;

	/**
	 * Sets up the test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->sut = new ProductIdCacheBuilder();
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );
	}

	/**
	 * Tears down the test environment.
	 */
	public function tear_down(): void {
		remove_filter(
			'woocommerce_product_data_store_cpt_get_products_query',
			array( $this->sut, 'query_products_by_meta' )
		);
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );
		as_unschedule_all_actions( Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ) );

		parent::tear_down();
	}

	/**
	 * A published product with no Snapchat sync setting is exported.
	 */
	public function test_includes_published_product_by_default(): void {
		$product_id = $this->create_product();

		$this->assertSame( array( $product_id ), $this->build_ids() );
	}

	/**
	 * Draft products are not exported.
	 */
	public function test_excludes_unpublished_product(): void {
		$this->create_product( array( 'status' => 'draft' ) );

		$this->assertSame( array(), $this->build_ids() );
	}

	/**
	 * The per-product Snapchat sync setting decides whether a product is exported.
	 */
	public function test_respects_snapchat_sync_setting(): void {
		$synced     = $this->create_product();
		$not_synced = $this->create_product();

		$meta_key = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );
		update_post_meta( $synced, $meta_key, '1' );
		update_post_meta( $not_synced, $meta_key, '0' );

		$this->assertSame( array( $synced ), $this->build_ids() );
	}

	/**
	 * A page with results queues the next page.
	 */
	public function test_queues_next_page_when_page_has_results(): void {
		$this->create_product();

		$this->sut->handle_batch( 1 );

		$this->assertTrue(
			as_has_scheduled_action(
				Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ),
				array( 'page' => 2 )
			)
		);
	}

	/**
	 * Runs the first page of the scan and returns the cached product IDs, sorted.
	 *
	 * @return int[]
	 */
	private function build_ids(): array {
		$this->sut->handle_batch( 1 );

		$ids = (array) Options::get( OptionDefaults::EXPORT_PRODUCT_IDS, array() );
		sort( $ids );

		return array_values( $ids );
	}

	/**
	 * Creates a simple product.
	 *
	 * @param array $props Product props to set, e.g. `status`, `catalog_visibility`, `stock_status`.
	 * @return int Product ID.
	 */
	private function create_product( array $props = array() ): int {
		$product = new WC_Product_Simple();
		$product->set_name( 'Test product' );
		$product->set_regular_price( 10 );
		$product->set_props( array_merge( array( 'status' => 'publish' ), $props ) );
		$product->save();

		return $product->get_id();
	}
}
