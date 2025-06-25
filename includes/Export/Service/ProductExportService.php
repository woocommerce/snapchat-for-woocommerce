<?php
/**
 * Action Scheduler service for exporting the Snapchat product catalog.
 *
 * Coordinates the batch-based export process using Action Scheduler. This service:
 * - Registers async hooks for cache building and export initiation.
 * - Clears previous export data.
 * - Delegates product scanning to a cache builder.
 * - Triggers paginated batch exports using {@see BatchExportJob}.
 *
 * @package SnapchatForWooCommerce\Export\Service
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Export\Service;

use SnapchatForWooCommerce\Export\Contract\CacheBuilderInterface;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Export\BatchExportJob;
use SnapchatForWooCommerce\Export\EntityProvider\ProductEntityProvider;
use SnapchatForWooCommerce\Export\RowBuilder\ProductRowBuilder;
use SnapchatForWooCommerce\Export\Writer\CsvExportWriter;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Handles batch-based product export via Action Scheduler.
 *
 * This service controls the full export pipeline. It begins by clearing any previous
 * export results, then invokes the cache builder to compute eligible product IDs.
 * Once the caching is completed (signaled via a custom hook), it initiates a sequence
 * of export batches using the {@see BatchExportJob} class.
 *
 * Each batch is offset-based and runs as an independent async job.
 *
 * @since 0.1.0
 */
class ProductExportService {

	/**
	 * Cache builder used to prepare the exportable product ID list.
	 *
	 * @var CacheBuilderInterface
	 */
	protected CacheBuilderInterface $cache_builder;

	/**
	 * Action Scheduler hook name for individual export batches.
	 *
	 * @since 0.1.0
	 */
	public const ACTION_HOOK = 'export_product_catalog';

	/**
	 * The class name of the Cache Builder.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private string $cache_builder_class = '';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CacheBuilderInterface $cache_builder Responsible for building and storing product ID cache.
	 */
	public function __construct( CacheBuilderInterface $cache_builder ) {
		$this->cache_builder       = $cache_builder;
		$this->cache_builder_class = get_class( $cache_builder );
	}

	/**
	 * Registers Action Scheduler hooks for the export process.
	 *
	 * - Hook to begin export after product caching is complete.
	 * - Hook to handle each export batch.
	 * - Delegates cache hook registration to the cache builder.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->cache_builder->register();

		add_action(
			Helper::with_prefix( 'export_products_cache_completed' ),
			array( $this, 'start_writing' )
		);

		add_action(
			Helper::with_prefix( self::ACTION_HOOK ),
			array( $this, 'handle_batch' ),
			10,
			2
		);
	}

	/**
	 * Initiates the product export by clearing previous state and triggering product scanning.
	 *
	 * This method:
	 * - Deletes previously saved file path and URL options.
	 * - Triggers the cache builder to compute exportable products.
	 * - Does not immediately begin file writing — that is triggered once caching completes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function start_export(): void {
		$scan_jobs   = as_has_scheduled_action( Helper::with_prefix( $this->cache_builder_class::ACTION_HOOK ) );
		$export_jobs = as_has_scheduled_action( Helper::with_prefix( self::ACTION_HOOK ) );

		if ( ! empty( $scan_jobs ) || ! empty( $export_jobs ) ) {
			return;
		}

		Options::delete( OptionDefaults::EXPORT_FILE_PATH );
		Options::delete( OptionDefaults::EXPORT_FILE_URL );

		if ( method_exists( $this->cache_builder, 'build_and_cache' ) ) {
			$this->cache_builder->build_and_cache();
		}
	}

	/**
	 * Starts writing the export file from offset 0.
	 *
	 * This method is hooked to run after exportable product IDs have been fully cached.
	 * It schedules the first export batch asynchronously.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function start_writing(): void {
		as_enqueue_async_action(
			Helper::with_prefix( self::ACTION_HOOK ),
			array( 'offset' => 0 ),
			Config::PLUGIN_SLUG
		);
	}

	/**
	 * Handles a single export batch.
	 *
	 * Each call creates and executes one instance of {@see BatchExportJob}.
	 * If this is the first batch, it creates a new file and stores its path.
	 * If additional batches are needed, this method schedules the next offset.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $offset Offset into the cached product ID list.
	 * @param string|null $existing_file Optional existing file path to continue writing to.
	 * @return void
	 */
	public function handle_batch( int $offset = 0, ?string $existing_file = null ): void {
		$provider    = new ProductEntityProvider();
		$row_builder = new ProductRowBuilder();
		$writer      = new CsvExportWriter();

		$job = new BatchExportJob( $provider, $row_builder, $writer );

		$is_first_batch = ( 0 === $offset );

		$result = $job->run( $offset, $is_first_batch, $existing_file );

		if ( $is_first_batch ) {
			Options::set( OptionDefaults::EXPORT_FILE_PATH, $result['file_path'] );
			Options::set( OptionDefaults::EXPORT_FILE_URL, $result['file_url'] );
		}

		if ( ! $result['complete'] ) {
			as_enqueue_async_action(
				Helper::with_prefix( self::ACTION_HOOK ),
				array(
					'offset'        => $offset + BatchExportJob::BATCH_SIZE,
					'existing_file' => $result['file_path'],
				),
				Config::PLUGIN_SLUG
			);
		}
	}
}
