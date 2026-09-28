<?php
/**
 * Integration tests for the RemotePixelTracker class.
 *
 * These tests validate that pixel injection behaves correctly based on plugin settings,
 * including caching, fallbacks, and integration with the WCS proxy layer.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Integration\Tracking;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Tracking\RemotePixelTracker;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Config;
use WC_Helper_Order;
use WC_Order;

/**
 * @covers \SnapchatForWooCommerce\Tracking\RemotePixelTracker
 */
class RemotePixelTrackerTest extends WP_UnitTestCase {

	/**
	 * Meta key used by the tracker to mark orders as already tracked.
	 */
	private const ORDER_PIXEL_TRACKED_META_KEY = '_snapchat_pixel_tracked';

	/**
	 * Script handle the purchase event is attached to.
	 */
	private const TRACKING_HANDLE = Config::ASSET_HANDLE_PREFIX . 'tracking';

	public function set_up(): void {
		parent::set_up();

		// Enable pixel tracking.
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );

		// Provide a default pixel script.
		Transients::set( TransientDefaults::PIXEL_SCRIPT, '<script src="https://sc-static.net/scevent.min.js"></script>' );

		// Provide a dummy ad account ID for API path construction.
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'fake-account-id' );
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::PIXEL_ENABLED );
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		Options::delete( OptionDefaults::COLLECT_PII );

		unset( $_GET['key'], $_POST['check_submission'], $_POST['email'] );
		set_query_var( 'order-received', '' );
		remove_filter( 'woocommerce_is_order_received_page', '__return_true' );
		wp_dequeue_script( self::TRACKING_HANDLE );
		wp_deregister_script( self::TRACKING_HANDLE );

		parent::tear_down();
	}

	/**
	 * Simulates a request to `/checkout/order-received/<order_id>/`.
	 *
	 * @param WC_Order    $order     Order being requested.
	 * @param string|null $order_key Value of the `key` query argument. Omitted when null.
	 */
	private function simulate_order_received_request( WC_Order $order, $order_key = null ): void {
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );
		set_query_var( 'order-received', (string) $order->get_id() );

		unset( $_GET['key'] );

		if ( null !== $order_key ) {
			$_GET['key'] = $order_key;
		}

		// The purchase event is attached to the tracking script, which must be registered.
		wp_register_script( self::TRACKING_HANDLE, 'https://example.org/tracking.js', array(), '1.0.0', true );
	}

	/**
	 * Returns the inline scripts attached to the tracking handle.
	 *
	 * @return string
	 */
	private function get_inline_tracking_script(): string {
		$data = wp_scripts()->get_data( self::TRACKING_HANDLE, 'after' );

		return is_array( $data ) ? implode( "\n", array_filter( $data ) ) : '';
	}

	/**
	 * Re-reads the order from the data store.
	 *
	 * @param WC_Order $order Order to reload.
	 * @return WC_Order
	 */
	private function reload_order( WC_Order $order ): WC_Order {
		return wc_get_order( $order->get_id() );
	}

	/**
	 * Test that a valid order key results in a complete PURCHASE payload.
	 */
	public function test_purchase_event_is_tracked_with_a_valid_order_key() {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = WC_Helper_Order::create_order( 0 );
		$this->simulate_order_received_request( $order, $order->get_order_key() );

		$tracker = new RemotePixelTracker( $this->createMock( WcsClient::class ) );
		$tracker->track_purchase_event();

		$script = $this->get_inline_tracking_script();

		$this->assertStringContainsString( 'snaptr("track", "PURCHASE"', $script );
		$this->assertStringContainsString( '"transaction_id":"' . $order->get_id() . '"', $script );
		$this->assertStringContainsString( '"currency":"' . $order->get_currency() . '"', $script );
		$this->assertStringContainsString( '"price":"' . $order->get_total() . '"', $script );
		$this->assertStringContainsString( '"number_items":4', $script );

		// Hashed billing identifiers are only added when PII collection is enabled.
		$this->assertStringContainsString( '"em":"' . hash( 'sha256', $order->get_billing_email() ) . '"', $script );
		$this->assertStringContainsString( '"ph":"', $script );
		$this->assertStringContainsString( '"fn":"', $script );
		$this->assertStringContainsString( '"ln":"', $script );
		$this->assertStringContainsString( '"ct":"', $script );
		$this->assertStringContainsString( '"zp":"', $script );
		$this->assertStringContainsString( '"country":"', $script );

		$this->assertSame( 1, (int) $this->reload_order( $order )->get_meta( self::ORDER_PIXEL_TRACKED_META_KEY, true ) );
	}

	/**
	 * Test that the event is not fired again when the confirmation page is reloaded.
	 */
	public function test_purchase_event_is_not_tracked_twice() {
		$order = WC_Helper_Order::create_order( 0 );
		$this->simulate_order_received_request( $order, $order->get_order_key() );

		$tracker = new RemotePixelTracker( $this->createMock( WcsClient::class ) );
		$tracker->track_purchase_event();

		$this->assertStringContainsString( 'snaptr("track", "PURCHASE"', $this->get_inline_tracking_script() );

		// Simulate a reload of the confirmation page.
		wp_deregister_script( self::TRACKING_HANDLE );
		$this->simulate_order_received_request( $order, $order->get_order_key() );
		$tracker->track_purchase_event();

		$this->assertSame( '', $this->get_inline_tracking_script() );
	}

	/**
	 * Test that no data is exposed when the order key is missing, wrong or malformed.
	 *
	 * @dataProvider provide_invalid_order_keys
	 *
	 * @param mixed $order_key Value of the `key` query argument, or null when it is omitted.
	 */
	public function test_purchase_event_is_not_tracked_without_a_valid_order_key( $order_key ) {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = WC_Helper_Order::create_order( 0 );
		$this->simulate_order_received_request( $order, $order_key );

		$tracker = new RemotePixelTracker( $this->createMock( WcsClient::class ) );
		$tracker->track_purchase_event();

		$this->assertSame( '', $this->get_inline_tracking_script() );

		// The order must stay untrackable so the customer still gets the event with a valid key.
		$this->assertSame( '', (string) $this->reload_order( $order )->get_meta( self::ORDER_PIXEL_TRACKED_META_KEY, true ) );

		// The real customer opening their own confirmation URL is still tracked.
		$this->simulate_order_received_request( $order, $order->get_order_key() );
		$tracker->track_purchase_event();

		$this->assertStringContainsString( 'snaptr("track", "PURCHASE"', $this->get_inline_tracking_script() );
	}

	/**
	 * Data provider of order keys WooCommerce would not grant access with.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public function provide_invalid_order_keys(): array {
		return array(
			'missing key'   => array( null ),
			'empty key'     => array( '' ),
			'wrong key'     => array( 'wc_order_notavalidkey' ),
			'malformed key' => array( array( 'wc_order_notavalidkey' ) ),
		);
	}

	/**
	 * Test that no event is added for an order past the email verification grace period until
	 * the visitor verifies their billing email, mirroring WooCommerce's own guest verification
	 * gate on the order-received page.
	 */
	public function test_purchase_event_is_not_tracked_when_email_verification_is_required() {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_date_created( time() - HOUR_IN_SECONDS );
		$order->save();

		$this->simulate_order_received_request( $order, $order->get_order_key() );

		$tracker = new RemotePixelTracker( $this->createMock( WcsClient::class ) );
		$tracker->track_purchase_event();

		$this->assertSame( '', $this->get_inline_tracking_script() );

		// Blocked request must not burn the tracked flag, so a later verified visit still fires.
		$this->assertSame( '', (string) $this->reload_order( $order )->get_meta( self::ORDER_PIXEL_TRACKED_META_KEY, true ) );
	}

	/**
	 * Test that supplying the correct billing email via the verify-email form results in exactly
	 * one PURCHASE event for an order past the email verification grace period.
	 */
	public function test_purchase_event_is_tracked_after_email_verification() {
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_date_created( time() - HOUR_IN_SECONDS );
		$order->save();

		$this->simulate_order_received_request( $order, $order->get_order_key() );

		$_POST['check_submission'] = wp_create_nonce( 'wc_verify_email' );
		$_POST['email']            = $order->get_billing_email();

		$tracker = new RemotePixelTracker( $this->createMock( WcsClient::class ) );
		$tracker->track_purchase_event();

		$this->assertStringContainsString( 'snaptr("track", "PURCHASE"', $this->get_inline_tracking_script() );
		$this->assertSame( 1, (int) $this->reload_order( $order )->get_meta( self::ORDER_PIXEL_TRACKED_META_KEY, true ) );
	}

	/**
	 * Test that the `woocommerce_thankyou_order_id` and `woocommerce_thankyou_order_key` filters
	 * are honoured, mirroring how WooCommerce resolves the order for a payment gateway that
	 * redirects with its own reference instead of the real order ID and key.
	 */
	public function test_purchase_event_is_tracked_when_order_id_and_key_are_remapped_by_filters() {
		$order = WC_Helper_Order::create_order( 0 );

		// Simulate a gateway redirect that carries neither the real order ID nor its key.
		$this->simulate_order_received_request( $order, 'gateway-reference' );
		set_query_var( 'order-received', '0' );

		$remap_order_id  = function () use ( $order ) {
			return $order->get_id();
		};
		$remap_order_key = function () use ( $order ) {
			return $order->get_order_key();
		};

		add_filter( 'woocommerce_thankyou_order_id', $remap_order_id );
		add_filter( 'woocommerce_thankyou_order_key', $remap_order_key );

		$tracker = new RemotePixelTracker( $this->createMock( WcsClient::class ) );
		$tracker->track_purchase_event();

		remove_filter( 'woocommerce_thankyou_order_id', $remap_order_id );
		remove_filter( 'woocommerce_thankyou_order_key', $remap_order_key );

		$this->assertStringContainsString( 'snaptr("track", "PURCHASE"', $this->get_inline_tracking_script() );
	}

	/**
	 * Test that an order of a registered customer is not tracked for other visitors,
	 * even when the order key is known.
	 */
	public function test_purchase_event_is_not_tracked_for_a_logged_out_known_shopper() {
		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$order       = WC_Helper_Order::create_order( $customer_id );

		$this->simulate_order_received_request( $order, $order->get_order_key() );

		$tracker = new RemotePixelTracker( $this->createMock( WcsClient::class ) );
		$tracker->track_purchase_event();

		$this->assertSame( '', $this->get_inline_tracking_script() );

		// The customer themselves is tracked as usual.
		wp_set_current_user( $customer_id );
		$tracker->track_purchase_event();

		$this->assertStringContainsString( 'snaptr("track", "PURCHASE"', $this->get_inline_tracking_script() );

		wp_set_current_user( 0 );
	}

	/**
	 * Test that no event is added outside of the order received page.
	 */
	public function test_purchase_event_is_not_tracked_outside_the_order_received_page() {
		$order = WC_Helper_Order::create_order( 0 );
		$this->simulate_order_received_request( $order, $order->get_order_key() );
		remove_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$tracker = new RemotePixelTracker( $this->createMock( WcsClient::class ) );
		$tracker->track_purchase_event();

		$this->assertSame( '', $this->get_inline_tracking_script() );
	}

	/**
	 * Test that the pixel script is rendered from cache if present.
	 */
	public function test_maybe_inject_pixel_outputs_cached_script() {
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );
		Transients::set( TransientDefaults::PIXEL_SCRIPT, '<script src="https://sc-static.net/scevent.min.js"></script>' );

		$wcs     = $this->createMock( WcsClient::class );
		$tracker = new RemotePixelTracker( $wcs );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script', $output );
		$this->assertStringContainsString( 'scevent.min.js', $output );
	}

	/**
	 * Test that the tracker fetches pixel script from WCS if not cached.
	 *
	 * It also asserts that the fetched script is cached for future use.
	 */
	public function test_pixel_script_fetched_from_wcs_and_cached() {
		Options::set( OptionDefaults::PIXEL_ID, 'snap-pixel-12345' );
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );

		$response_mock = $this->createMock( \WP_REST_Response::class );
		$response_mock->method( 'get_data' )->willReturn(
			array(
				'pixels' => array(
					array( 'pixel' => array( 'pixel_javascript' => '<script src="https://sc-static.net/scevent.min.js"></script>' ) ),
				),
			)
		);

		$client_mock = $this->createMock( WcsClient::class );
		$client_mock->method( 'proxy_get' )->willReturn( $response_mock );

		$tracker = new RemotePixelTracker( $client_mock );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'https://sc-static.net/scevent.min.js', $output );
	}

	/**
	 * Test that nothing is output if authentication fails.
	 */
	public function test_returns_null_if_authentication_fails() {
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );

		$client_mock = $this->createMock( WcsClient::class );

		$tracker = new RemotePixelTracker( $client_mock );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}
}
