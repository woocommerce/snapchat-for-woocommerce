<?php
/**
 * Scans and caches exportable product IDs in a memory-safe way.
 *
 * This builder queries WooCommerce products in pages and stores
 * only those with the meta key defined in Config::CATALOG_ITEM.
 *
 * Accepts dynamic query arguments to support filtered or scoped
 * exports (e.g., by category, stock status, tag, or date).
 *
 * @package SnapchatForWooCommerce\Export\Service
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Export\Service;

use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Export\ExportConstants;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Prepares a stable snapshot of exportable product IDs.
 *
 * Called before batch export begins, to ensure that the list of
 * products remains consistent across all Action Scheduler jobs.
 *
 * This class performs a paged WC_Product_Query to avoid memory overload
 * on large catalogs. Matching IDs are stored in the WordPress options table
 * for fast lookup and deterministic batching during export.
 *
 * @since 0.1.0
 */
class ProductIdCacheBuilder {

	/**
	 * Number of products to query per page.
	 *
	 * Controls the size of each WC_Product_Query pagination cycle.
	 * Adjust this constant if performance tuning is needed.
	 *
	 * @since 0.1.0
	 */
	const BATCH_SIZE = 50;

	/**
	 * Optional query arguments to filter the product search.
	 *
	 * Supports any arguments accepted by WC_Product_Query,
	 * such as category, stock_status, tag, etc.
	 *
	 * These will be merged with the default constraints,
	 * but cannot override pagination or export-specific filters.
	 *
	 * @since 0.1.0
	 *
	 * @var array<string,mixed>
	 */
	protected array $query_args = array();

	/**
	 * Constructor.
	 *
	 * Accepts optional query arguments to customize which products
	 * are included in the ID scan (e.g., category-specific export).
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $query_args Optional WC_Product_Query args.
	 */
	public function __construct( array $query_args = [] ) {
		$this->query_args = $query_args;
	}

	/**
	 * Queries all exportable product IDs and stores them in a WordPress option.
	 *
	 * Performs a paged scan of products that match the meta key defined in
	 * ExportConstants::CATALOG_ITEM (e.g., "snapchat_product_catalog_item" = true).
	 *
	 * This operation is safe for large catalogs due to pagination,
	 * and results in a consistent ID list used by downstream export jobs.
	 *
	 * The cached list is stored in OptionDefaults::EXPORT_PRODUCT_IDS and
	 * read by ProductEntityProvider for deterministic batch slicing.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function build_and_cache(): void {
		$page    = isset( $this->query_args['page'] ) ? (int) $this->query_args['page'] : 1;
		$all_ids = array();

		do {
			$default_args = array(
				'limit'      => self::BATCH_SIZE,
				'page'       => $page,
				'status'     => 'publish',
				'return'     => 'ids',
				'meta_key'   => Helper::with_prefix( ExportConstants::CATALOG_ITEM ),
				'meta_value' => true,
			);

			// Do not allow caller to override pagination or filtering logic.
			$query_args = array_merge( $this->query_args, $default_args );

			$query   = new \WC_Product_Query( $query_args );
			$results = $query->get_products();

			$result_count = count( $results );

			if ( 0 === $result_count ) {
				break;
			}

			$all_ids = array_merge( $all_ids, array_map( 'intval', $results ) );
			++$page;
		} while ( self::BATCH_SIZE === $result_count );

		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, $all_ids );
	}
}
