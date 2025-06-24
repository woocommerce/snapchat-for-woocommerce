<?php
/**
 * Action Scheduler service for exporting the Snapchat product catalog.
 *
 * Coordinates one batch of export at a time using the generic exporter engine.
 *
 * @package SnapchatForWooCommerce\Export\Service
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Export\Service;

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
 * Each job processes one batch and schedules the next if required.
 *
 * @since 0.1.0
 */
class ProductExportService {

	/**
	 * Action Scheduler hook name.
	 *
	 * @since 0.1.0
	 */
	public const ACTION_HOOK = 'export_product_catalog';

	/**
	 * Registers the async Action Scheduler hook.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( Helper::with_prefix( self::ACTION_HOOK ), array( $this, 'handle_batch' ), 10, 2 );
	}

	/**
	 * Schedules the first export batch.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function start_export(): void {
		Options::delete( OptionDefaults::EXPORT_FILE_PATH );
		Options::delete( OptionDefaults::EXPORT_FILE_URL );
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );

		as_enqueue_async_action(
			Helper::with_prefix( self::ACTION_HOOK ),
			array( 'offset' => 0 ),
			Config::PLUGIN_SLUG
		);
	}

	/**
	 * Handles a single export batch.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $offset Offset into product list.
	 * @param string|null $existing_file Optional existing file path to append to.
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
