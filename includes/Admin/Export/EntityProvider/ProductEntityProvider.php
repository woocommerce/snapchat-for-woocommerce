<?php
/**
 * Provides exportable WooCommerce products for Snapchat catalog sync.
 *
 * This implementation retrieves product IDs previously cached by {@see ProductIdCacheBuilder}
 * and exposes them in paginated batches. It performs memory-safe operations using
 * `LIMIT` + `OFFSET` logic, and resolves each ID into a `WC_Product` object.
 *
 * Only products with the custom meta key defined in
 * {@see \SnapchatForWooCommerce\Admin\ProductMeta\ProductMetaFields::CATALOG_ITEM}
 * and a value of `1`, `yes`, or `true` are included in the cached export list.
 *
 * @package SnapchatForWooCommerce\Admin\Export\EntityProvider
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\EntityProvider;

use WC_Product;
use SnapchatForWooCommerce\Admin\Export\Contract\ExportableEntityProviderInterface;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Entity provider for exportable WooCommerce products.
 *
 * This class implements the {@see ExportableEntityProviderInterface} to supply
 * product data in a batch-safe format for the export pipeline.
 *
 * It fetches product IDs from the cached list stored in WordPress options
 * (populated by {@see ProductIdCacheBuilder}) and converts them into `WC_Product`
 * objects for use in exporters like {@see BatchExportJob}.
 *
 * @since 0.1.0
 */
class ProductEntityProvider implements ExportableEntityProviderInterface {

	/**
	 * Returns the total number of products eligible for export.
	 *
	 * Products are considered eligible if they are included in the cached ID list,
	 * which is built ahead of time by the cache builder. This avoids re-querying
	 * the database during export and ensures consistency across batches.
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
	 * Uses array slicing on the cached product ID list to simulate
	 * offset-based pagination, ensuring efficient memory use.
	 *
	 * @since 0.1.0
	 *
	 * @param int $offset Zero-based offset into the cached list.
	 * @param int $limit  Maximum number of product IDs to return.
	 * @return array<int> List of product IDs for the current batch.
	 */
	public function get_ids( int $offset, int $limit ): array {
		$cached = Options::get( OptionDefaults::EXPORT_PRODUCT_IDS, array() );
		return array_slice( $cached, $offset, $limit );
	}

	/**
	 * Resolves the given product IDs into `WC_Product` instances.
	 *
	 * Skips IDs that do not resolve to valid product objects.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int> $ids List of WooCommerce product post IDs.
	 * @return array<int, WC_Product> List of valid product entities.
	 */
	public function get_entities( array $ids ): array {
		return wc_get_products(
			array(
				'include' => $ids,
				'return'  => 'objects',
				'limit'   => count( $ids ),
			)
		);
	}
}
