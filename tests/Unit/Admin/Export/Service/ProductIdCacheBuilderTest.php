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
		remove_filter(
			'woocommerce_product_data_store_cpt_get_products_query',
			array( $this->sut, 'query_catalog_visible_products' )
		);
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );
		delete_option( 'woocommerce_hide_out_of_stock_items' );
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
	 * Products shown in the shop are exported, whether or not they also appear in search.
	 */
	public function test_includes_products_visible_in_catalog(): void {
		$shop_and_search = $this->create_product( array( 'catalog_visibility' => 'visible' ) );
		$shop_only       = $this->create_product( array( 'catalog_visibility' => 'catalog' ) );

		$this->assertSame( array( $shop_and_search, $shop_only ), $this->build_ids() );
	}

	/**
	 * Products hidden from the store catalog are not exported, matching the Channel visibility notice.
	 */
	public function test_excludes_products_hidden_from_catalog(): void {
		$visible = $this->create_product();
		$this->create_product( array( 'catalog_visibility' => 'hidden' ) );
		$this->create_product( array( 'catalog_visibility' => 'search' ) );

		$this->assertSame( array( $visible ), $this->build_ids() );
	}

	/**
	 * Out-of-stock products are not exported when the store hides them from the catalog.
	 */
	public function test_excludes_out_of_stock_products_when_store_hides_them(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$in_stock = $this->create_product();
		$this->create_product( array( 'stock_status' => 'outofstock' ) );

		$this->assertSame( array( $in_stock ), $this->build_ids() );
	}

	/**
	 * Out-of-stock products are still exported when the store shows them in the catalog.
	 */
	public function test_includes_out_of_stock_products_when_store_shows_them(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );

		$in_stock     = $this->create_product();
		$out_of_stock = $this->create_product( array( 'stock_status' => 'outofstock' ) );

		$this->assertSame( array( $in_stock, $out_of_stock ), $this->build_ids() );
	}

	/**
	 * The catalog visibility filter adds to an existing tax query instead of replacing it.
	 */
	public function test_catalog_visibility_filter_keeps_existing_tax_query(): void {
		$existing = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => array( 'simple' ),
		);

		$query = $this->sut->query_catalog_visible_products(
			array( 'tax_query' => array( $existing ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array( 'snap_visible' => true )
		);

		$this->assertSame( $existing, $query['tax_query'][0] );
		$this->assertSame( 'product_visibility', $query['tax_query'][1]['taxonomy'] );
		$this->assertSame( array( 'exclude-from-catalog' ), $query['tax_query'][1]['terms'] );
		$this->assertSame( 'NOT IN', $query['tax_query'][1]['operator'] );
	}

	/**
	 * The catalog visibility filter leaves other product queries alone.
	 */
	public function test_catalog_visibility_filter_ignores_other_queries(): void {
		$query = array( 'post_type' => 'product' );

		$this->assertSame( $query, $this->sut->query_catalog_visible_products( $query, array() ) );
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
	 * The scan completes on the first page when no products are eligible, so the export still runs.
	 */
	public function test_completes_on_first_page_when_no_products_are_eligible(): void {
		$this->create_product( array( 'catalog_visibility' => 'hidden' ) );

		$completed = did_action( Helper::with_prefix( 'export_products_cache_completed' ) );

		$this->sut->handle_batch( 1 );

		$this->assertSame( $completed + 1, did_action( Helper::with_prefix( 'export_products_cache_completed' ) ) );
		$this->assertFalse( as_has_scheduled_action( Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ) ) );
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
