<?php
/**
 * Unit tests for the ProductRowBuilder class.
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\Export\RowBuilder;

use WC_Product_Simple;
use WP_UnitTestCase;
use SnapchatForWooCommerce\Admin\Export\RowBuilder\ProductRowBuilder;

/**
 * @covers \SnapchatForWooCommerce\Admin\Export\RowBuilder\ProductRowBuilder
 */
class ProductRowBuilderTest extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var ProductRowBuilder
	 */
	private $row_builder;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->row_builder = new ProductRowBuilder();
	}

	/**
	 * Tests that build_row() returns an array with expected keys.
	 */
	public function test_build_row_contains_expected_keys() {
		$product = $this->create_test_product();

		$row = $this->row_builder->build_row( $product );

		$this->assertIsArray( $row );
		$this->assertArrayHasKey( 'id', $row );
		$this->assertArrayHasKey( 'title', $row );
		$this->assertArrayHasKey( 'price', $row );
		$this->assertArrayHasKey( 'availability', $row );
		$this->assertArrayHasKey( 'description', $row );
		$this->assertSame( 'Test Description', $row['description'] ); // stripped of HTML
	}

	/**
	 * Tests that the price field includes the correct currency suffix.
	 */
	public function test_build_row_price_appends_currency() {
		$product = $this->create_test_product( array(
			'regular_price' => '199.99',
		) );

		$currency = get_woocommerce_currency();
		$row      = $this->row_builder->build_row( $product );

		$this->assertSame( '199.99 ' . $currency, $row['price'] );
	}

	/**
	 * Creates a simple WC_Product instance with optional overrides.
	 *
	 * @param array $props Optional product properties.
	 * @return WC_Product_Simple
	 */
	private function create_test_product( array $props = array() ) {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_description( '<p>Test Description</p>' );
		$product->set_regular_price( '199.99' );
		$product->save();

		return $product;
	}
}
