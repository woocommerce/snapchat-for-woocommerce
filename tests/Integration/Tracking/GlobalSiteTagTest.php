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
use SnapchatForWooCommerce\Config;
use WC_Helper_Product;

/**
 * @covers \SnapchatForWooCommerce\Tracking\GlobalSiteTag
 */
class GlobalSiteTagTest extends WP_UnitTestCase {

	private GlobalSiteTag $service;

	public function set_up(): void {
		parent::set_up();
		$this->service = new GlobalSiteTag();
		$this->service->register();
	}

	public function tear_down(): void {
		global $wp_scripts;
		$wp_scripts = null; // Reset WP_Scripts for isolated tests.
		parent::tear_down();
	}

	/**
	 * Tests that the expected WooCommerce and WordPress hooks are registered by GlobalSiteTag.
	 *
	 * This includes hooks for product data collection, single product view tracking,
	 * order confirmation tracking, and data localization in the footer.
	 */
	public function test_hooks_are_registered() {
		$this->assertNotFalse( has_filter( 'woocommerce_loop_add_to_cart_link' ) );
		$this->assertNotFalse( has_action( 'woocommerce_after_add_to_cart_button' ) );
		$this->assertNotFalse( has_action( 'woocommerce_after_single_product' ) );
		$this->assertNotFalse( has_action( 'woocommerce_before_thankyou' ) );
		$this->assertNotFalse( has_action( 'wp_footer' ) );
	}

	/**
	 * Tests that the VIEW_CONTENT tracking event is added as an inline script
	 * when viewing a single product page.
	 *
	 * Verifies:
	 * - The script is registered.
	 * - The inline script contains the correct snaptr call with VIEW_CONTENT.
	 */
	public function test_track_view_content_event_adds_inline_script() {
		$product = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		global $post;
		$post = get_post( $product_id );

		wp_register_script( Config::ASSET_HANDLE_PREFIX . 'pixel-tracking', false );
		$this->service->track_view_content_event();
		$scripts = wp_scripts();

		$this->assertArrayHasKey( Config::ASSET_HANDLE_PREFIX . 'pixel-tracking', $scripts->registered );
		$registered = $scripts->registered[ Config::ASSET_HANDLE_PREFIX . 'pixel-tracking' ];

		$this->assertStringContainsString( 'snaptr("track", "VIEW_CONTENT"', implode( '', $registered->extra['after'] ?? [] ) );

		wp_reset_postdata();
	}

	/**
	 * Tests that the PURCHASE tracking event is added as an inline script
	 * when the order confirmation page is viewed.
	 *
	 * Verifies:
	 * - The order is marked as tracked via meta.
	 * - The inline script includes the PURCHASE event with correct product ID.
	 */
	public function test_track_purchase_event_sets_meta_and_adds_inline_script() {
		$product = \WC_Helper_Product::create_simple_product();

		$order = wc_create_order();
		$order->add_product( $product, 2 );
		$order->calculate_totals();
		$order->save();

		// Force WooCommerce to treat this as the order received page.
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		wp_register_script( Config::ASSET_HANDLE_PREFIX . 'pixel-tracking', false );

		$this->service->track_purchase_event( $order->get_id() );

		$order = wc_get_order( $order->get_id() ); // Refresh order to get updated meta
		$this->assertEquals( 1, $order->get_meta( '_snapchat_pixel_tracked', true ) );

		$scripts = wp_scripts();
		$inline  = implode( '', $scripts->registered[ Config::ASSET_HANDLE_PREFIX . 'pixel-tracking' ]->extra['after'] ?? [] );

		$this->assertStringContainsString( 'snaptr("track", "PURCHASE"', $inline );
		$this->assertStringContainsString( (string) $product->get_id(), $inline );

		remove_filter( 'woocommerce_is_order_received_page', '__return_true' );
	}

	/**
	 * Tests that the localized global JavaScript variable is printed to the footer.
	 *
	 * Verifies:
	 * - The output contains the expected global variable name.
	 * - The output contains `currency` and `products` keys used by the pixel script.
	 */
	public function test_localize_data_outputs_global_variable_script() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		global $post;
		$post = get_post( $product_id );
		setup_postdata( $post );

		$this->service->track_view_content_event();

		ob_start();
		$this->service->localize_data();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'const ' . Config::AD_PARTNER_JS_GLOBAL, $output );
		$this->assertStringContainsString( 'currency', $output );
		$this->assertStringContainsString( 'products', $output );

		wp_reset_postdata();
	}
}
