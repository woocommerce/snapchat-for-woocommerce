<?php
/**
 * Contract for writing export data to a destination (e.g., file, stream).
 *
 * This interface defines methods for creating, writing to, and retrieving
 * the export file or stream that stores final output.
 *
 * @package SnapchatForWooCommerce\Admin\Export\Contract
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\Contract;

/**
 * Interface for writing export rows to disk or a streaming destination.
 *
 * This allows pluggable output formats (CSV, JSON, etc.) and supports
 * writing rows incrementally across multiple batches.
 *
 * @since 0.1.0
 */
interface ExportWriterInterface {

	/**
	 * Creates a new export file and returns its absolute file path.
	 *
	 * This is typically called during the first batch.
	 *
	 * @since 0.1.0
	 *
	 * @return string Absolute file path to the new export file.
	 */
	public function create_file(): string;

	/**
	 * Writes the header row (column names) to the file.
	 *
	 * Should only be called once, immediately after file creation.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file_path The export file path to write into.
	 * @return void
	 */
	public function write_header( string $file_path ): void;

	/**
	 * Appends a single row of data to the export file.
	 *
	 * @since 0.1.0
	 *
	 * @param string                $file_path The export file path to write into.
	 * @param array<string, scalar> $row Associative row data to write.
	 * @return void
	 */
	public function append_row( string $file_path, array $row ): void;

	/**
	 * Returns a publicly accessible URL for the generated file.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file_path Full file path on disk.
	 * @return string Public URL to access the file.
	 */
	public function generate_url( string $file_path ): string;
}
