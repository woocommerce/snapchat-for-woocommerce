<?php
/**
 * Unit test for OrderAttributionData class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit
 */

namespace SnapchatForWooCommerce\Tests\Unit;

use WP_UnitTestCase;
use WC_Helper_Order;
use SnapchatForWooCommerce\Admin\MetaBox\OrderAttributionData;

/**
 * @covers \SnapchatForWooCommerce\Admin\MetaBox\OrderAttributionData
 */
final class OrderAttributionDataTest extends WP_UnitTestCase {

	/**
	 * Resolver under test.
	 *
	 * @var OrderAttributionData
	 */
	private OrderAttributionData $order_attribution_data;

	public function set_up(): void {
		parent::set_up();

		$this->order_attribution_data = new OrderAttributionData();
	}

	public function tear_down(): void {
		unset( $_GET['id'], $_GET['post'] );

		parent::tear_down();
	}

	public function test_returns_null_without_order_id(): void {
		$this->assertNull(
			$this->order_attribution_data->get_order_attribution_source_for_edit_screen()
		);
	}

	public function test_returns_null_for_invalid_order_id(): void {
		$_GET['id'] = 999999;

		$this->assertNull(
			$this->order_attribution_data->get_order_attribution_source_for_edit_screen()
		);
	}

	public function test_returns_null_for_non_snapchat_source(): void {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( OrderAttributionData::UTM_SOURCE_META_KEY, 'newsletter' );
		$order->save();

		$_GET['id'] = $order->get_id();

		$this->assertNull(
			$this->order_attribution_data->get_order_attribution_source_for_edit_screen()
		);
	}

	public function test_returns_snapchat_for_snapchat_source(): void {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( OrderAttributionData::UTM_SOURCE_META_KEY, 'snapchat' );
		$order->save();

		$_GET['id'] = $order->get_id();

		$this->assertSame(
			OrderAttributionData::SNAPCHAT_SOURCE,
			$this->order_attribution_data->get_order_attribution_source_for_edit_screen()
		);
	}

	public function test_source_match_is_case_insensitive_and_trimmed(): void {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( OrderAttributionData::UTM_SOURCE_META_KEY, '  Snapchat  ' );
		$order->save();

		$_GET['id'] = $order->get_id();

		$this->assertSame(
			OrderAttributionData::SNAPCHAT_SOURCE,
			$this->order_attribution_data->get_order_attribution_source_for_edit_screen()
		);
	}

	public function test_resolves_order_from_legacy_post_param(): void {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( OrderAttributionData::UTM_SOURCE_META_KEY, 'snapchat' );
		$order->save();

		$_GET['post'] = $order->get_id();

		$this->assertSame(
			OrderAttributionData::SNAPCHAT_SOURCE,
			$this->order_attribution_data->get_order_attribution_source_for_edit_screen()
		);
	}
}
