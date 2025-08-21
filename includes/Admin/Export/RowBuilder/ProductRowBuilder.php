<?php
/**
 * Builds exportable CSV rows from WooCommerce product entities.
 *
 * This class transforms a `WC_Product` instance into an associative array of
 * fields required by Snapchat's product catalog format. It ensures that
 * all expected keys (such as title, price, and availability) are present,
 * and performs minimal transformation where needed (e.g., appending currency to price).
 *
 * @package SnapchatForWooCommerce\Admin\Export\RowBuilder
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\RowBuilder;

use WC_Product;
use SnapchatForWooCommerce\Admin\Export\Contract\ExportRowBuilderInterface;

/**
 * Converts WooCommerce products into catalog-compatible row arrays.
 *
 * Implements {@see ExportRowBuilderInterface} to define how a WooCommerce product
 * should be exported into a format suitable for Snapchat’s product feed.
 * Handles common fields such as title, price, and availability, and attempts
 * to extract optional fields like brand, GTIN, and MPN from product attributes or meta.
 *
 * This class is intended to be reused across batch exporters or file writers.
 *
 * @since 0.1.0
 */
class ProductRowBuilder implements ExportRowBuilderInterface {

	/**
	 * Builds a single exportable row from a product entity.
	 *
	 * Validates that the input is a `WC_Product`, then extracts relevant product data
	 * into an associative array with Snapchat catalog keys. If the input is not a product,
	 * returns `null` to skip the row.
	 *
	 * The resulting row includes:
	 * - Core attributes like ID, title, description, image, and price
	 * - Inventory status
	 * - Optional metadata: brand, GTIN, and MPN
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $product A `WC_Product` instance expected.
	 * @return array<string,scalar>|null Associative export row or null to skip.
	 */
	public function build_row( $product ): ?array {
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';

		$price        = $product->get_price();
		$is_price_set = '' !== $price;
		$currency     = get_woocommerce_currency();

		return array(
			'id'           => (string) $product->get_id(),
			'title'        => $product->get_name(),
			'description'  => $product->get_description(),
			'link'         => get_permalink( $product->get_id() ),
			'image_link'   => $image_url,
			/**
			 * In case the price is not set, we set the availability to 'Out of stock'
			 * as that indicates the product is not available for purchase.
			 *
			 * However, if the price is explicitly set to `0`, we consider it as 'In stock'
			 * as the product can be sold as a free product (for example, a free digital download).
			 */
			'availability' => $product->is_in_stock() && $is_price_set ? 'In stock' : 'Out of stock',
			'price'        => ( $is_price_set ? $price : '0' ) . ' ' . $currency,
			'gtin'         => $product->get_global_unique_id(),
		);
	}
}
