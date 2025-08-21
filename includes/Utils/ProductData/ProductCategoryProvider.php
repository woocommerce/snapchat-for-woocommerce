<?php
/**
 * Provides the Google Product Category (GPC) field for WooCommerce products.
 *
 * This class inspects the WooCommerce product categories assigned to a product
 * and derives a breadcrumb-style string representation (e.g.,
 * "Apparel & Accessories > Clothing > Activewear"). The resulting value is
 * returned as an associative array suitable for merging into an export row or
 * any other product payload.
 *
 * By implementing {@see RowBuilderAdditionalData}, this provider can be injected
 * into {@see ProductRowBuilder} or any similar row builder, ensuring that
 * product category logic remains isolated, reusable, and testable.
 *
 * @package SnapchatForWooCommerce\Utils\ProductData
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\ProductData;

use WC_Product;
use WP_Term;
use SnapchatForWooCommerce\Admin\Export\Contract\RowBuilderAdditionalData;

/**
 * Derives the google_product_category field from WooCommerce product categories.
 *
 * The provider walks the term hierarchy to build a breadcrumb-style path,
 * ensuring that deeper category context is captured. The final string is
 * truncated to 250 characters, in accordance with Google’s product data
 * specification.
 *
 * @since 0.1.0
 */
class ProductCategoryProvider implements RowBuilderAdditionalData {

	/**
	 * Build the google_product_category value for a given product.
	 *
	 * Returns an associative array with a single key 'google_product_category'.
	 * If the product has no categories, an empty array is returned to avoid
	 * polluting the export row with invalid data.
	 *
	 * Example:
	 * [
	 *   'google_product_category' => 'Apparel & Accessories > Clothing > Activewear'
	 * ]
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product Product instance.
	 * @return array<string,string> Associative array with GPC field or empty if unavailable.
	 */
	public function get_additional_data( $product ): array {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		// Select the deepest assigned category.
		usort(
			$terms,
			function ( $a, $b ) {
				return count( get_ancestors( $b->term_id, 'product_cat' ) )
					- count( get_ancestors( $a->term_id, 'product_cat' ) );
			}
		);

		$primary = array_shift( $terms );

		// Build breadcrumb-style hierarchy.
		$ancestors = array_reverse( get_ancestors( $primary->term_id, 'product_cat' ) );

		// Prime caches for all ancestor terms in a single call.
		_prime_term_caches( $ancestors, 'product_cat' );

		$categories = array();

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );

			if ( ! is_wp_error( $ancestor ) && $ancestor instanceof WP_Term ) {
				$categories[] = $ancestor->name;
			}
		}

		$categories[] = $primary->name;
		$gpc          = implode( ' > ', $categories );

		return array(
			'google_product_category' => substr( $gpc, 0, 250 ),
		);
	}
}
