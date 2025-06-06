<?php
/**
 * Integration tests for the GlobalSiteTag class.
 *
 * This test suite validates that the GlobalSiteTag hooks register correctly
 * and that key pixel events can be triggered on the frontend.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Integration\Tracking;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Tracking\GlobalSiteTag;

/**
 * @covers \SnapchatForWooCommerce\Tracking\GlobalSiteTag
 */
class GlobalSiteTagTest extends WP_UnitTestCase {

	/**
	 * Service under test.
	 *
	 * @var GlobalSiteTag
	 */
	private $service;

	public function set_up(): void {
		parent::set_up();

		$this->service = new GlobalSiteTag();
		$this->service->register();
	}

	/**
	 * Tests that GlobalSiteTag hooks are registered.
	 */
	public function test_hooks_are_registered() {
		$this->assertNotFalse( has_filter( 'woocommerce_loop_add_to_cart_link' ) );
		$this->assertNotFalse( has_action( 'woocommerce_after_add_to_cart_button' ) );
		$this->assertNotFalse( has_action( 'woocommerce_after_single_product' ) );
		$this->assertNotFalse( has_action( 'woocommerce_before_thankyou' ) );
		$this->assertNotFalse( has_action( 'wp_footer' ) );
	}

	/**
	 * Tests that track_view_content_event() runs without error.
	 *
	 * Simulates a single product page view.
	 */
	public function test_track_view_content_event_runs_without_error() {
		$product = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		setup_postdata( get_post( $product_id ) );

		ob_start();
		$this->service->track_view_content_event();
		ob_end_clean();

		wp_reset_postdata();

		$this->assertTrue( true );
	}

	/**
	 * Tests that track_purchase_event() runs without error.
	 *
	 * Simulates an order confirmation scenario.
	 */
	public function test_track_purchase_event_runs_without_error() {
		$order = wc_create_order();
		$product = \WC_Helper_Product::create_simple_product();

		$order->add_product( $product, 2 );
		$order->calculate_totals();
		$order->save();

		// Simulate order_received_page context.
		add_filter( 'is_order_received_page', '__return_true' );

		ob_start();
		$this->service->track_purchase_event( $order->get_id() );
		ob_end_clean();

		remove_filter( 'is_order_received_page', '__return_true' );

		$this->assertTrue( true );
	}

	/**
	 * Tests that localize_data() runs without error.
	 *
	 * We do not assert on actual JS output here — that belongs in E2E test.
	 */
	public function test_localize_data_runs_without_error() {
		ob_start();
		$this->service->localize_data();
		ob_end_clean();

		$this->assertTrue( true );
	}
}
