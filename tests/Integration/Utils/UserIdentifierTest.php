<?php
/**
 * Tests for the order related parts of the UserIdentifier class.
 *
 * @package SnapchatForWooCommerce\Tests\Utils
 */

namespace SnapchatForWooCommerce\Tests\Utils;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Utils\UserIdentifier;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use WC_Helper_Order;
use WC_Order;

/**
 * @covers \SnapchatForWooCommerce\Utils\UserIdentifier
 */
class UserIdentifierTest extends WP_UnitTestCase {

	/**
	 * Hashed billing identifiers built from the order.
	 *
	 * @var string[]
	 */
	private const BILLING_KEYS = array( 'em', 'ph', 'fn', 'ln', 'ct', 'zp', 'country' );

	public function set_up(): void {
		parent::set_up();

		Options::set( OptionDefaults::COLLECT_PII, 'yes' );
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::COLLECT_PII );

		unset( $_GET['key'] );
		set_query_var( 'order-received', '' );
		remove_filter( 'woocommerce_is_order_received_page', '__return_true' );

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
	}

	/**
	 * Test that billing identifiers are hashed into the user data of an authorized request.
	 */
	public function test_get_user_data_includes_billing_identifiers_with_a_valid_order_key() {
		$order = WC_Helper_Order::create_order( 0 );
		$this->simulate_order_received_request( $order, $order->get_order_key() );

		$data = UserIdentifier::get_user_data();

		foreach ( self::BILLING_KEYS as $key ) {
			$this->assertArrayHasKey( $key, $data );
		}

		$this->assertSame( hash( 'sha256', $order->get_billing_email() ), $data['em'] );
	}

	/**
	 * Test that no billing identifier is exposed when the order key is missing or wrong.
	 *
	 * @dataProvider provide_invalid_order_keys
	 *
	 * @param mixed $order_key Value of the `key` query argument, or null when it is omitted.
	 */
	public function test_get_user_data_omits_billing_identifiers_without_a_valid_order_key( $order_key ) {
		$order = WC_Helper_Order::create_order( 0 );
		$this->simulate_order_received_request( $order, $order_key );

		$data = UserIdentifier::get_user_data();

		foreach ( self::BILLING_KEYS as $key ) {
			$this->assertArrayNotHasKey( $key, $data );
		}
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
	 * Test that an explicitly passed order is used as-is, e.g. by the Pixel tracker which
	 * has already verified the request.
	 */
	public function test_add_user_details_uses_the_given_order() {
		$order = WC_Helper_Order::create_order( 0 );
		$data  = array();

		UserIdentifier::add_user_details( $data, $order );

		$this->assertSame( hash( 'sha256', $order->get_billing_email() ), $data['em'] );
	}
}
