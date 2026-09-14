<?php
/**
 * Resolves WooCommerce order attribution data for the order edit screen.
 *
 * Provides screen detection and attribution-source lookup used to decide
 * whether the Snapchat meta box promo should be rendered on an order.
 *
 * @package SnapchatForWooCommerce\Admin\MetaBox
 * @since 1.1.0
 */

namespace SnapchatForWooCommerce\Admin\MetaBox;

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Reads order attribution data on the WooCommerce order edit screen.
 *
 * @since 1.1.0
 */
class OrderAttributionData {

	/**
	 * Meta key storing the order attribution UTM source.
	 *
	 * WooCommerce persists the attribution source under this hidden meta key
	 * for both HPOS and legacy post-based orders.
	 *
	 * @since 1.1.0
	 */
	public const UTM_SOURCE_META_KEY = '_wc_order_attribution_utm_source';

	/**
	 * Attribution source value that identifies a Snapchat-attributed order.
	 *
	 * @since 1.1.0
	 */
	public const SNAPCHAT_SOURCE = 'snapchat';

	/**
	 * Determines whether the current admin screen is a WooCommerce order edit screen.
	 *
	 * Handles both HPOS and legacy post-based order screens.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True when viewing a `shop_order` edit screen.
	 */
	public function is_wc_order_edit_screen(): bool {
		return OrderUtil::is_order_edit_screen( 'shop_order' );
	}

	/**
	 * Resolves the attribution source for the order being edited.
	 *
	 * Reads the order id from the request (`id` for HPOS, `post` for legacy),
	 * loads the order, and returns {@see self::SNAPCHAT_SOURCE} only when the
	 * stored UTM source is an exact Snapchat match.
	 *
	 * @since 1.1.0
	 *
	 * @return string|null The Snapchat source string, or null when not Snapchat-attributed.
	 */
	public function get_order_attribution_source_for_edit_screen(): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id = absint( wp_unslash( $_GET['id'] ?? $_GET['post'] ?? 0 ) );

		if ( 0 === $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return null;
		}

		$source = strtolower( trim( (string) $order->get_meta( self::UTM_SOURCE_META_KEY ) ) );

		if ( self::SNAPCHAT_SOURCE !== $source ) {
			return null;
		}

		return self::SNAPCHAT_SOURCE;
	}
}
