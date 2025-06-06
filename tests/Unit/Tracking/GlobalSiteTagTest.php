<?php
/**
 * Unit tests for the GlobalSiteTag class.
 *
 * This test suite validates the behavior of the GlobalSiteTag in isolation.
 * It confirms that product data collection and GTAG data structure are correct.
 *
 * Note: These are pure unit tests. Integration with actual frontend rendering and hooks
 * is covered in integration tests.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Unit\Tracking;

use WP_UnitTestCase;
use WC_Product_Simple;
use SnapchatForWooCommerce\Tracking\GlobalSiteTag;

/**
 * @covers \SnapchatForWooCommerce\Tracking\GlobalSiteTag
 */
class GlobalSiteTagTest extends WP_UnitTestCase {

	/**
	 * Tests that get_gtag_data() returns expected structure after adding a product.
	 */
	public function test_get_gtag_data_returns_expected_structure_after_adding_product() {
		$tag = new GlobalSiteTag();

		$product = new WC_Product_Simple();
		$product->set_regular_price( 42.99 );
		$product->save();

		// Manually invoke add_product_data (protected method).
		$ref = new \ReflectionClass( $tag );
		$method = $ref->getMethod( 'add_product_data' );
		$method->setAccessible( true );
		$method->invoke( $tag, $product );

		$data = $tag->get_gtag_data();

		$this->assertArrayHasKey( 'currency_minor_unit', $data );
		$this->assertArrayHasKey( 'currency', $data );
		$this->assertArrayHasKey( 'products', $data );

		$this->assertArrayHasKey( $product->get_id(), $data['products'] );
		$this->assertIsArray( $data['products'][ $product->get_id() ] );
		$this->assertArrayHasKey( 'price', $data['products'][ $product->get_id() ] );
		$this->assertEquals( 42.99, $data['products'][ $product->get_id() ]['price'] );
	}
}
