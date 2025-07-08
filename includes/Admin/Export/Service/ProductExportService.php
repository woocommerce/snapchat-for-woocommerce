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
 * @package SnapchatForWooCommerce\Admin\Export\Service
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\Service;

use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Admin\Export\BatchExportJob;
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
	 * Action Scheduler hook name for individual export batches.
	 *
	 * @since 0.1.0
	 */
	public const ACTION_HOOK = 'export_product_catalog';

	/**
	 * Encapsulates the logic for scanning product IDs, building export rows, and writing to file.
	 *
	 * This reusable job runner handles each export batch, and is invoked
	 * during both cache completion and asynchronous Action Scheduler execution.
	 *
	 * @since 0.1.0
	 *
	 * @var BatchExportJob
	 */
	protected BatchExportJob $job;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param BatchExportJob $job Export job instance that handles caching, entity export, and file generation.
	 */
	public function __construct( BatchExportJob $job ) {
		$this->job = $job;
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
	public function register_hooks(): void {
		$this->job->cache_builder->register();

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
	 * Validates that the export writer can create and delete a file.
	 *
	 * This method ensures the filesystem is writable before any export jobs begin.
	 * It creates a temporary file using the export writer and removes it using
	 * the WordPress filesystem API.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 *
	 * @throws \RuntimeException If the filesystem is not writable or cleanup fails.
	 */
	protected function validate_export_environment(): void {
		try {
			$file_path = $this->job->writer->create_file();

			global $wp_filesystem;

			if ( ! $wp_filesystem || ! is_a( $wp_filesystem, \WP_Filesystem_Base::class ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			if ( $wp_filesystem->exists( $file_path ) ) {
				$wp_filesystem->delete( $file_path );
			}
		} catch ( \RuntimeException $e ) {
			throw new \RuntimeException(
				//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				'Export aborted. Filesystem is not ready: ' . $e->getMessage(),
				0,
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				$e
			);
		}
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
	 * @return bool
	 */
	public function start_export(): bool {
		try {
			$this->validate_export_environment();
		} catch ( \RuntimeException $e ) {
			return false;
		}

		if ( $this->job->is_job_in_progress( self::ACTION_HOOK ) ) {
			return false;
		}

		Options::delete( OptionDefaults::EXPORT_FILE_PATH );
		Options::delete( OptionDefaults::EXPORT_FILE_URL );

		if ( method_exists( $this->job->cache_builder, 'build_and_cache' ) ) {
			$this->job->cache_builder->build_and_cache();
		}

		return true;
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
		$is_first_batch = ( 0 === $offset );

		$result = $this->job->run( $offset, $is_first_batch, $existing_file );

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
