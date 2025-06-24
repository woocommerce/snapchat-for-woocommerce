<?php
/**
 * Builds exportable CSV rows from WooCommerce product entities.
 *
 * Converts a WC_Product instance into a row matching Snapchat's product
 * catalog field specifications.
 *
 * @package SnapchatForWooCommerce\Export\RowBuilder
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Export\RowBuilder;

use WC_Product;
use SnapchatForWooCommerce\Export\Contract\ExportRowBuilderInterface;

/**
 * Converts WooCommerce products into catalog-compatible row arrays.
 *
 * This builder returns rows with keys matching Snapchat's required
 * and recommended product catalog fields.
 *
 * @since 0.1.0
 */
class ProductRowBuilder implements ExportRowBuilderInterface {

	/**
	 * Builds a single exportable row from a product.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $product WC_Product instance expected.
	 * @return array<string, scalar>|null Associative row or null to skip.
	 */
	public function build_row( $product ): ?array {
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';

		$price    = $product->get_price();
		$currency = get_woocommerce_currency();

		return array(
			'id'           => (string) $product->get_id(),
			'title'        => $product->get_name(),
			'description'  => $product->get_description(),
			'link'         => get_permalink( $product->get_id() ),
			'image_link'   => $image_url,
			'availability' => $product->is_in_stock() ? 'In stock' : 'Out of stock',
			'price'        => $price . ' ' . $currency,
			'brand'        => $product->get_attribute( 'brand' ), // Fallback: could check meta or custom attribute
			'gtin'         => get_post_meta( $product->get_id(), 'gtin', true ),
			'mpn'          => get_post_meta( $product->get_id(), 'mpn', true ),
		);
	}
}
