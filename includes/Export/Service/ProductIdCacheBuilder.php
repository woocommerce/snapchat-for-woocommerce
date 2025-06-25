<?php
/**
 * Queues and caches exportable product IDs in a memory-safe way using Action Scheduler.
 *
 * This service class scans eligible WooCommerce products for export in paginated batches,
 * stores the resulting product IDs in a persistent WordPress option, and coordinates
 * the batch processing using Action Scheduler.
 *
 * It ensures memory safety and execution time limits by scanning one page per job,
 * making it suitable for large catalogs with thousands of products.
 *
 * @package SnapchatForWooCommerce\Export\Service
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Export\Service;

use SnapchatForWooCommerce\Export\Contract\CacheBuilderInterface;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Export\ExportConstants;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Queues product ID scanning in pages and stores results in a shared WordPress option.
 *
 * This class is responsible for building a list of exportable product IDs. It executes
 * one Action Scheduler job per page of results, filtering only those products marked
 * with the custom meta key indicating export eligibility.
 *
 * The full list is cached in {@see OptionDefaults::EXPORT_PRODUCT_IDS} for later use by
 * export workers like {@see ProductExportService}.
 *
 * Caching the list of exportable product IDs before the actual export ensures consistency,
 * even if new products are added or existing ones are removed during the export process.
 * This avoids potential inconsistencies in batch boundaries caused by a changing product catalog.
 *
 * @since 0.1.0
 */
class ProductIdCacheBuilder implements CacheBuilderInterface {

	/**
	 * Action Scheduler hook name for scanning each page of product IDs.
	 *
	 * This hook is scheduled once per page in the exportable product list.
	 *
	 * @since 0.1.0
	 */
	public const ACTION_HOOK = 'scan_exportable_product_ids_page';

	/**
	 * Number of products to query per page.
	 *
	 * Increasing this may reduce the number of jobs at the cost of memory usage.
	 *
	 * @since 0.1.0
	 */
	const BATCH_SIZE = 50;

	/**
	 * Registers the Action Scheduler hook for scanning product IDs.
	 *
	 * This method is typically called during plugin initialization.
	 * It binds the asynchronous hook name to the `handle_batch()` method.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		add_action(
			Helper::with_prefix( self::ACTION_HOOK ),
			array( $this, 'handle_batch' ),
			10,
			1
		);
	}

	/**
	 * Starts the product scanning process.
	 *
	 * Clears any previously cached product IDs and enqueues the first page for processing.
	 * This method is typically triggered manually before export starts.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function build_and_cache(): void {
		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, array() );

		as_enqueue_async_action(
			Helper::with_prefix( self::ACTION_HOOK ),
			array( 'page' => 1 ),
			Config::PLUGIN_SLUG
		);
	}

	/**
	 * Processes a single page of exportable products and schedules the next page.
	 *
	 * This method is invoked via Action Scheduler. It retrieves one page of product
	 * IDs that match the export eligibility meta key, appends them to the cached list,
	 * and enqueues the next page.
	 *
	 * If the current page is empty and not the first, it fires a custom action to
	 * indicate that the caching process has completed.
	 *
	 * @since 0.1.0
	 *
	 * @param int $page The current page to process (starts at 1).
	 * @return void
	 */
	public function handle_batch( int $page ): void {
		$page = $page > 1 ? $page : 1;

		$query_args = array(
			'limit'      => self::BATCH_SIZE,
			'page'       => $page,
			'status'     => 'publish',
			'return'     => 'ids',
			'meta_key'   => Helper::with_prefix( ExportConstants::CATALOG_ITEM ),
			'meta_value' => true,
		);

		$query   = new \WC_Product_Query( $query_args );
		$results = $query->get_products();

		if ( empty( $results ) ) {
			if ( 1 !== $page ) {
				do_action( Helper::with_prefix( 'export_products_cache_completed' ) );
			}
			return;
		}

		$existing = Options::get( OptionDefaults::EXPORT_PRODUCT_IDS, array() );
		$existing = array_unique( array_merge( $existing, array_map( 'intval', $results ) ) );

		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, $existing );

		as_enqueue_async_action(
			Helper::with_prefix( self::ACTION_HOOK ),
			array( 'page' => $page + 1 ),
			Config::PLUGIN_SLUG
		);
	}
}
