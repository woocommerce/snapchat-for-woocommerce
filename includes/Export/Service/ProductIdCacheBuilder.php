<?php
/**
 * Queues and caches exportable product IDs in a memory-safe way using Action Scheduler.
 *
 * Each page of products is scanned in a separate async job, allowing large catalogs
 * to be processed without exhausting memory or execution time.
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
 * Queues product ID scanning in pages, stores results in a shared WordPress option.
 *
 * @since 0.1.0
 */
class ProductIdCacheBuilder implements CacheBuilderInterface {
	/**
	 * Action Scheduler hook name for scanning each page of IDs.
	 *
	 * @since 0.1.0
	 */
	public const ACTION_HOOK = 'scan_exportable_product_ids_page';

	/**
	 * Number of products to query per page.
	 *
	 * @since 0.1.0
	 */
	const BATCH_SIZE = 50;

	/**
	 * Registers the Action Scheduler hook for ID scanning.
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
	 * Starts the scanning process by clearing old data and enqueueing page 1.
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
	 * Scans one page of exportable product IDs and schedules the next if needed.
	 *
	 * @param int $page Must contain 'page'.
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
