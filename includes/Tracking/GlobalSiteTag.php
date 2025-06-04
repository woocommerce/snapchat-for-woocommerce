<?php
namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Config;
use WC_Product;
use function wc_get_price_to_display;
use function wc_get_price_decimals;

defined( 'ABSPATH' ) || exit;

final class GlobalSiteTag {
	/**
	 * Collected product data for localization.
	 *
	 * @var array
	 */
	protected array $products = [];

	protected const ORDER_CONVERSION_META_KEY = '_snap_pixel_tracked';

	/**
	 * Register WordPress hooks and localization for gtag-events.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_loop_add_to_cart_link',
			function ( $link, $product ) {
				if ( $product instanceof WC_Product ) {
					$this->add_product_data( $product );
				}
				return $link;
			},
			10,
			2
		);

		add_action(
			'woocommerce_after_add_to_cart_button',
			function() {
				global $product;

				if ( $product instanceof WC_Product ) {
					$this->add_product_data( $product );
				}
			}
		);

		add_action(
			'woocommerce_before_thankyou',
			array( $this, 'track_purchase_event' )
		);

		add_action(
			'wp_footer',
			array( $this, 'localize_data' )
		);
	}

	/**
	 * Return the localized data required by gtag-events.
	 *
	 * @return array
	 */
	public function get_gtag_data(): array {
		$data = array(
			'currency_minor_unit' => wc_get_price_decimals(),
			'currency'            => get_woocommerce_currency(),
			'products'            => $this->products,
		);

		return $data;
	}

	/**
	 * Add product tracking metadata.
	 *
	 * @param WC_Product $product
	 */
	protected function add_product_data( WC_Product $product ): void {
		$this->products[ $product->get_id() ] = [
			'price' => wc_get_price_to_display( $product ),
		];
	}

	public function localize_data() {
		wp_print_inline_script_tag( sprintf( 'const snapchatAdsData = %s', wp_json_encode( $this->get_gtag_data() ) ) );
	}

	public function track_purchase_event( $order_id ) {
		if ( ! is_order_received_page() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		// Make sure there is a valid order object and it is not already marked as tracked.
		if ( ! $order || 1 === (int) $order->get_meta( self::ORDER_CONVERSION_META_KEY, true ) ) {
			return;
		}

		// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
		$order->update_meta_data( self::ORDER_CONVERSION_META_KEY, 1 );
		$order->save_meta_data();

		$transaction_id  = $order->get_order_number();
		$total           = $order->get_total();
		$currency        = $order->get_currency();
		$item_ids        = [];
		$item_categories = [];
		$number_items    = 0;

		foreach ( $order->get_items() as $item ) {
			/** @var \WC_Order_Item_Product $item */
			$product = $item->get_product();
			if ( $product ) {
				$item_ids[] = $product->get_id();
				$number_items += $item->get_quantity();

				$terms = get_the_terms( $product->get_id(), 'product_cat' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$item_categories[] = $term->name;
					}
				}
			}
		}

		$tracking_data = sprintf(
			'snaptr("track", "PURCHASE", %s);',
			wp_json_encode( array(
				'price'             => $total,
				'currency'          => $currency,
				'transaction_id'    => $transaction_id,
				'item_ids'          => $item_ids,
				'item_category'     => implode( ', ', array_unique( $item_categories ) ),
				'number_items'      => $number_items,
			) )
		);

		wp_add_inline_script( Config::ASSET_HANDLE_PREFIX . 'pixel-tracking', $tracking_data );
	}
}
