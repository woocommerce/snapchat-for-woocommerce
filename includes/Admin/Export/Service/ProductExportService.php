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
	public BatchExportJob $job;

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
			Helper::with_prefix( 'onboarding_complete' ),
			array( $this, 'start_export_after_onboarding' )
		);

		add_action(
			Helper::with_prefix( 'snapchat_disconnected' ),
			array( $this, 'maybe_unschedule_export_jobs' )
		);

		add_action(
			Helper::with_prefix( 'recurring_catalog_export' ),
			array( $this, 'start_export' )
		);

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

		add_action(
			'wp_ajax_' . Helper::with_prefix( 'generate_feed' ),
			array( $this, 'trigger_export_callback' )
		);

		add_filter(
			'wp_ajax_' . Helper::with_prefix( 'export_status' ),
			array( $this, 'check_export_status' ),
			10
		);

		add_action(
			Helper::with_prefix( 'batch_export_job_complete' ),
			array( $this, 'create_feed' )
		);
	}

	/**
	 * Initiates the export process immediately after onboarding and schedules daily recurring exports.
	 *
	 * This method is triggered via the {@see Helper::with_prefix( 'onboarding_complete' )} hook.
	 * It performs the following:
	 * - Initiates a one-time product catalog export by calling {@see self::start_export()}.
	 * - Schedules a daily recurring export job using Action Scheduler if not already scheduled.
	 *
	 * This ensures that merchants begin with a fresh export and that exports continue automatically.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function start_export_after_onboarding(): void {
		$this->start_export();
		$this->maybe_schedule_recurring_export();
	}

	/**
	 * Schedules the recurring catalog export job if not already scheduled.
	 *
	 * This method registers a daily recurring job via Action Scheduler using the hook:
	 * {@see Helper::with_prefix( 'recurring_catalog_export' )}.
	 *
	 * The job is set to begin one day after the current time and repeats every 24 hours.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function maybe_schedule_recurring_export(): void {
		if ( ! as_has_scheduled_action( Helper::with_prefix( 'recurring_catalog_export' ) ) ) {
			as_schedule_recurring_action(
				time() + DAY_IN_SECONDS,
				DAY_IN_SECONDS,
				Helper::with_prefix( 'recurring_catalog_export' ),
				array(),
				Config::PLUGIN_SLUG
			);
		}
	}

	/**
	 * Unschedules all export-related Action Scheduler jobs.
	 *
	 * This method clears any scheduled export actions, including:
	 * - The main export job.
	 * - Recurring export jobs.
	 *
	 * It is typically called when the Snapchat connection is removed or during plugin deactivation.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function maybe_unschedule_export_jobs(): void {
		as_unschedule_all_actions( Helper::with_prefix( self::ACTION_HOOK ) );
		as_unschedule_all_actions( Helper::with_prefix( get_class( $this->job->cache_builder )::ACTION_HOOK ) );
		as_unschedule_all_actions( Helper::with_prefix( 'recurring_catalog_export' ) );
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
	 * @return bool|null
	 */
	public function start_export() {
		try {
			$this->validate_export_environment();
		} catch ( \RuntimeException $e ) {
			return false;
		}

		if ( $this->job->is_job_in_progress( self::ACTION_HOOK ) ) {
			return null;
		}

		Options::delete( OptionDefaults::EXPORT_FILE_PATH );
		Options::delete( OptionDefaults::EXPORT_FILE_URL );
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );

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

		$result      = $this->job->run( $offset, $is_first_batch, $existing_file );
		$is_complete = $result['complete'];
		$on_complete = function () use ( $result ) {
			Options::set( OptionDefaults::EXPORT_FILE_PATH, $result['file_path'] );
			Options::set( OptionDefaults::EXPORT_FILE_URL, $result['file_url'] );
			$this->job->set_timestamp();
		};

		if ( $is_first_batch && $is_complete ) {
			$this->job->on_complete( $on_complete );
			return;
		}

		if ( ! $is_complete ) {
			as_enqueue_async_action(
				Helper::with_prefix( self::ACTION_HOOK ),
				array(
					'offset'        => $offset + BatchExportJob::BATCH_SIZE,
					'existing_file' => $result['file_path'],
				),
				Config::PLUGIN_SLUG
			);
		} else {
			$this->job->on_complete( $on_complete );
		}
	}

	/**
	 * Handles the AJAX request to initiate the product catalog export.
	 *
	 * This method:
	 * - Verifies the security nonce.
	 * - Attempts to start the export process.
	 * - Sends a JSON success or error response.
	 *
	 * @since 0.1.0
	 */
	public function trigger_export_callback(): void {
		check_ajax_referer( 'export-nonce', 'security' );

		if ( ! Helper::has_products() ) {
			wp_send_json_error( array( 'code' => Helper::with_prefix( 'no_products_found' ) ) );
		}

		$status = $this->start_export();

		if ( true === $status || null === $status ) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Responds to Heartbeat API requests to report the current export status.
	 *
	 * This is used by the frontend to check if an export is idle, in progress,
	 * or completed, and to return the file URL if available.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function check_export_status() {
		check_ajax_referer( 'export-nonce', 'security' );

		$is_job_in_progress = $this->job->is_job_in_progress( self::ACTION_HOOK );
		$file_url           = Options::get( OptionDefaults::EXPORT_FILE_URL );
		$status             = 'idle';

		if ( $is_job_in_progress && empty( $file_url ) ) {
			$status = 'in-progress';
		} elseif ( ! empty( $file_url ) && ! $is_job_in_progress ) {
			$status = 'completed';
		}

		$response = array(
			'status'     => $status,
			'fileUrl'    => $file_url,
			'lastExport' => Helper::get_formatted_timestamp( Options::get( OptionDefaults::LAST_EXPORT_TIMESTAMP ) ),
		);

		wp_send_json( $response );
	}

	/**
	 * Invokes the Ad Partner API to create a product feed after export completes.
	 *
	 * This method is hooked to {@see Helper::with_prefix( 'batch_export_job_complete' )}.
	 * It is triggered automatically after the final export batch has finished and the
	 * CSV feed file has been successfully written.
	 *
	 * - Calls {@see FeedApi::create()} to register the feed with the Ad Partner.
	 * - Logs an alert if feed creation fails with a WP_Error.
	 *
	 * This final step ensures that the exported product catalog is registered
	 * as a feed with the Ad Partner for ingestion and use in Dynamic Ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function create_feed() {
		/**
		 * Only create the feed if it hasn't been created yet.
		 */
		if ( 'empty' === Options::get( OptionDefaults::FEED_STATUS ) ) {
			$response = $this->job->ad_partner_api->feed->create();

			if ( is_wp_error( $response ) ) {
				if ( Helper::is_logging_enabled() ) {
					$logger = wc_get_logger();
					$logger->alert(
						'Feed generation failed with error code' . $response->get_error_code(),
					);
				}
			} else {
				Options::set( OptionDefaults::FEED_STATUS, 'created' );
			}
		}
	}
}
