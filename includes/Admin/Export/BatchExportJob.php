<?php
/**
 * Generic export job runner that processes entities in paginated batches.
 *
 * This class coordinates a single batch in the product catalog export process by:
 * - Retrieving a page of exportable entity IDs from the provider
 * - Resolving those entities into usable objects
 * - Converting each entity into a structured row
 * - Appending the rows to a CSV export file
 *
 * It is designed to be reused with Action Scheduler or similar async execution engines
 * to iteratively generate large exports in chunks.
 *
 * @package SnapchatForWooCommerce\Admin\Export
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export;

use SnapchatForWooCommerce\Admin\Export\Contract\ExportableEntityProviderInterface;
use SnapchatForWooCommerce\Admin\Export\Contract\ExportRowBuilderInterface;
use SnapchatForWooCommerce\Admin\Export\Contract\ExportWriterInterface;
use SnapchatForWooCommerce\Admin\Export\Contract\CacheBuilderInterface;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\API\AdPartner\AdPartnerApi;
use WC_Logger_Interface;

/**
 * Executes a single export batch for any entity type.
 *
 * This class encapsulates the logic for processing one offset-based chunk of exportable data,
 * including file creation (for the first batch), header row writing, and entity resolution.
 * It returns metadata including the generated file path, file URL, and a flag indicating
 * whether the export is complete.
 *
 * Can be composed into multi-batch workflows via tools like Action Scheduler.
 *
 * @since 0.1.0
 */
class BatchExportJob {

	/**
	 * Number of entities to export in each batch.
	 *
	 * Adjust this constant to control memory usage and execution time per batch.
	 *
	 * @since 0.1.0
	 */
	const BATCH_SIZE = 50;

	/**
	 * Cache builder used to prepare the exportable product ID list.
	 *
	 * @var CacheBuilderInterface
	 */
	public CacheBuilderInterface $cache_builder;

	/**
	 * Supplies entity IDs and objects to be exported.
	 *
	 * @var ExportableEntityProviderInterface
	 */
	protected ExportableEntityProviderInterface $provider;

	/**
	 * Converts each entity into an array of exportable row data.
	 *
	 * @var ExportRowBuilderInterface
	 */
	protected ExportRowBuilderInterface $row_builder;

	/**
	 * Writes row data to the final export file (e.g., CSV).
	 *
	 * @var ExportWriterInterface
	 */
	public ExportWriterInterface $writer;

	/**
	 * Provides access to the Ad Partner APIs.
	 *
	 * @var AdPartnerApi
	 */
	public AdPartnerApi $ad_partner_api;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CacheBuilderInterface             $cache_builder  Prepares and caches exportable entity IDs ahead of export.
	 * @param ExportableEntityProviderInterface $provider       Supplies exportable entity IDs and objects.
	 * @param ExportRowBuilderInterface         $row_builder    Builds CSV-compatible row data from entities.
	 * @param ExportWriterInterface             $writer         Writes data to file and manages file output.
	 * @param AdPartnerApi                      $ad_partner_api Exposes Ad Partner APIs.
	 */
	public function __construct(
		CacheBuilderInterface $cache_builder,
		ExportableEntityProviderInterface $provider,
		ExportRowBuilderInterface $row_builder,
		ExportWriterInterface $writer,
		AdPartnerApi $ad_partner_api
	) {
		$this->cache_builder  = $cache_builder;
		$this->provider       = $provider;
		$this->row_builder    = $row_builder;
		$this->writer         = $writer;
		$this->ad_partner_api = $ad_partner_api;
	}

	/**
	 * Executes a single export batch at the given offset.
	 *
	 * This method performs one step in the export pipeline by processing
	 * a page of entities. If it's the first batch, it creates a new export file
	 * and writes the header. Otherwise, it appends data to the given file.
	 *
	 * It returns information about the file and whether additional batches are required.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $offset          Zero-based batch offset (i.e., page number).
	 * @param bool        $is_first_batch  Whether this is the first batch (triggering file creation).
	 * @param string|null $existing_file   Path to existing file if continuing a previous export.
	 * @return array{
	 *     file_path: string,
	 *     file_url: string,
	 *     complete: bool
	 * } Metadata about the file and batch completion status.
	 */
	public function run( int $offset, bool $is_first_batch = false, ?string $existing_file = null ): array {
		$ids = $this->provider->get_ids( $offset, self::BATCH_SIZE );

		if ( empty( $ids ) ) {
			return array(
				'file_path' => $existing_file ?? '',
				'file_url'  => '',
				'complete'  => true,
			);
		}

		$file_path = $existing_file;

		if ( $is_first_batch || ! $file_path ) {
			$file_path = $this->writer->create_file();
			$this->writer->write_header( $file_path );
		}

		$entities = $this->provider->get_entities( $ids );

		foreach ( $entities as $entity ) {
			$row = $this->row_builder->build_row( $entity );

			if ( is_array( $row ) ) {
				$this->writer->append_row( $file_path, $row );
			}
		}

		$file_url = $this->writer->generate_url( $file_path );
		$total    = $this->provider->get_total();
		$next     = $offset + self::BATCH_SIZE;

		return array(
			'file_path' => $file_path,
			'file_url'  => $file_url,
			'complete'  => $next >= $total,
		);
	}

	/**
	 * Determines whether any export-related Action Scheduler jobs are currently pending.
	 *
	 * This method checks if there are any queued or running async jobs associated with
	 * the export process. It looks for:
	 * - Scanning jobs triggered by the cache builder (e.g., scan_exportable_product_ids_page)
	 * - Export writing jobs triggered by this service (e.g., export_product_catalog)
	 *
	 * Useful to prevent duplicate job scheduling or overlapping exports.
	 *
	 * @since 0.1.0
	 *
	 * @param string $entity_export_id Unique export hook name (e.g., export_product_catalog).
	 * @return bool True if any export or cache jobs are in progress, false otherwise.
	 */
	public function is_job_in_progress( string $entity_export_id ): bool {
		$scan_jobs   = as_has_scheduled_action( Helper::with_prefix( $entity_export_id ) );
		$export_jobs = as_has_scheduled_action( Helper::with_prefix( get_class( $this->cache_builder )::ACTION_HOOK ) );

		if ( ! ( empty( $scan_jobs ) && empty( $export_jobs ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Updates the last export timestamp in plugin options.
	 *
	 * This is stored for audit or display purposes after a successful export run.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function set_timestamp(): void {
		Options::set( OptionDefaults::LAST_EXPORT_TIMESTAMP, time() );
	}

	/**
	 * Finalizes the export batch and triggers post-export actions.
	 *
	 * This method is called when a batch export process has completed. It performs
	 * post-export tasks.
	 *
	 * This is typically invoked by the export orchestration layer after all entities
	 * in the batch have been successfully exported.
	 *
	 * @since 0.1.0
	 *
	 * @param callable $callback A user-defined function or method to be executed after export completion.
	 * @return void
	 */
	public function on_complete( callable $callback ): void {
		if ( is_callable( $callback ) ) {
			$callback();
		}

		/**
		 * Fires when a batch export job has been completed.
		 *
		 * This hook indicates that all export tasks in the current batch have finished processing.
		 * It can be used to trigger post-export actions, such as cleanup, notification, or further data processing.
		 *
		 * @since 0.1.0
		 */
		do_action( Helper::with_prefix( 'batch_export_job_complete' ) );
	}
}
