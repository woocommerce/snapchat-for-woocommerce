<?php
/**
 * Generic export job runner that processes entities in paginated batches.
 *
 * This class coordinates one export batch:
 * - Retrieves a page of entity IDs
 * - Resolves those entities
 * - Converts them into rows
 * - Appends them to the export file
 *
 * @package SnapchatForWooCommerce\Export
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Export;

use SnapchatForWooCommerce\Export\Contract\ExportableEntityProviderInterface;
use SnapchatForWooCommerce\Export\Contract\ExportRowBuilderInterface;
use SnapchatForWooCommerce\Export\Contract\ExportWriterInterface;

/**
 * Executes a single export batch for any entity type.
 *
 * This class is designed to be reused with Action Scheduler or any async system
 * that handles offset-based batch work.
 *
 * @since 0.1.0
 */
class BatchExportJob {

	/**
	 * Number of entities to export in each batch.
	 *
	 * @since 0.1.0
	 */
	const BATCH_SIZE = 20;

	/**
	 * @var ExportableEntityProviderInterface
	 */
	protected ExportableEntityProviderInterface $provider;

	/**
	 * @var ExportRowBuilderInterface
	 */
	protected ExportRowBuilderInterface $row_builder;

	/**
	 * @var ExportWriterInterface
	 */
	protected ExportWriterInterface $writer;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param ExportableEntityProviderInterface $provider    Supplies exportable entity IDs.
	 * @param ExportRowBuilderInterface         $row_builder Builds CSV rows from each entity.
	 * @param ExportWriterInterface             $writer      Appends rows to export file.
	 */
	public function __construct(
		ExportableEntityProviderInterface $provider,
		ExportRowBuilderInterface $row_builder,
		ExportWriterInterface $writer
	) {
		$this->provider    = $provider;
		$this->row_builder = $row_builder;
		$this->writer      = $writer;
	}

	/**
	 * Executes a single export batch at the given offset.
	 *
	 * If this is the first batch, it creates a new file and writes the header row.
	 * Otherwise, it appends to the provided file.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $offset Zero-based batch offset.
	 * @param bool        $is_first_batch Whether to create the file and write headers.
	 * @param string|null $existing_file Existing file to append to.
	 * @return array{file_path: string, file_url: string, complete: bool}
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
}
