<?php
/**
 * Provides exportable WooCommerce products for Snapchat catalog sync.
 *
 * This implementation performs batch-safe queries using LIMIT and OFFSET,
 * ensuring memory efficiency for stores with large product catalogs.
 *
 * Only products with the meta key defined in {@see \SnapchatForWooCommerce\Config::CATALOG_ITEM}
 * and a value of '1', 'yes', or 'true' will be considered exportable.
 *
 * @package SnapchatForWooCommerce\Export\EntityProvider
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Export\EntityProvider;

use WC_Product;
use SnapchatForWooCommerce\Export\Contract\ExportableEntityProviderInterface;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;


/**
 * Entity provider for exportable WooCommerce products.
 *
 * Retrieves product IDs marked for export and resolves them into
 * `WC_Product` objects in paginated batches. Designed for compatibility
 * with Action Scheduler-based exports.
 *
 * @since 0.1.0
 */
class ProductEntityProvider implements ExportableEntityProviderInterface {

	/**
	 * Returns the total number of products eligible for export.
	 *
	 * A product is considered eligible if it has the meta key
	 * {@see \SnapchatForWooCommerce\Config::CATALOG_ITEM} with a truthy value.
	 *
	 * @since 0.1.0
	 *
	 * @return int Total number of exportable products.
	 */
	public function get_total(): int {
		$cached = Options::get( OptionDefaults::EXPORT_PRODUCT_IDS, array() );
		return count( $cached );
	}

	/**
	 * Returns a paginated list of exportable WooCommerce product IDs.
	 *
	 * Uses a LIMIT + OFFSET SQL query to safely load IDs in chunks.
	 *
	 * @since 0.1.0
	 *
	 * @param int $offset Zero-based offset.
	 * @param int $limit  Maximum number of IDs to return.
	 * @return array<int> List of product IDs for this batch.
	 */
	public function get_ids( int $offset, int $limit ): array {
		$cached = Options::get( OptionDefaults::EXPORT_PRODUCT_IDS, array() );
		return array_slice( $cached, $offset, $limit );
	}

	/**
	 * Resolves the given product IDs into `WC_Product` instances.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int> $ids List of WooCommerce product post IDs.
	 * @return array<int, WC_Product> List of valid product objects.
	 */
	public function get_entities( array $ids ): array {
		$products = array();

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );

			if ( $product instanceof WC_Product ) {
				$products[] = $product;
			}
		}

		return $products;
	}
}
