<?php
/**
 * Writes export rows to a CSV file using WP_Filesystem.
 *
 * This implementation writes to the WordPress uploads directory and
 * generates a public download URL from the same.
 *
 * @package SnapchatForWooCommerce\Admin\Export\Writer
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\Writer;

use SnapchatForWooCommerce\Admin\Export\Contract\ExportWriterInterface;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use WP_Filesystem_Direct;

/**
 * Concrete export writer that generates a CSV file.
 *
 * Uses WP_Filesystem to support environments with different filesystem access layers.
 * Supports creating a new file, writing header rows, appending data rows,
 * and resolving a public download URL.
 *
 * @since 0.1.0
 */
class CsvExportWriter implements ExportWriterInterface {

	/**
	 * Name of the subdirectory inside uploads/ to store exports.
	 *
	 * @since 0.1.0
	 */
	const EXPORT_FOLDER = 'snapchat';

	/**
	 * Filesystem handler.
	 *
	 * @var WP_Filesystem_Direct
	 */
	protected $fs;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->fs = $wp_filesystem;
	}

	/**
	 * Creates a new CSV file in the uploads directory.
	 *
	 * @since 0.1.0
	 *
	 * @todo Move the Filesystem checks to a base class.
	 *
	 * @return string Absolute path to the newly created file.
	 * @throws \RuntimeException If upload directory is missing, export folder can't be created, or file creation fails.
	 */
	public function create_file(): string {
		$upload_dir = wp_upload_dir();

		if ( empty( $upload_dir['basedir'] ) || ! is_dir( $upload_dir['basedir'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \RuntimeException( 'Unable to determine upload directory.' );
		}

		$dir_path = trailingslashit( $upload_dir['basedir'] ) . self::EXPORT_FOLDER;

		if ( ! $this->fs->is_dir( $dir_path ) ) {
			wp_mkdir_p( $dir_path );

			if ( ! $this->fs->is_dir( $dir_path ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \RuntimeException( 'Failed to create export directory: ' . $dir_path );
			}
		}

		$filename = 'products-' . Options::get( OptionDefaults::WCS_PRODUCTS_TOKEN ) . '.csv';
		$file     = trailingslashit( $dir_path ) . $filename;

		$success = $this->fs->put_contents( $file, '' );

		if ( ! $success ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \RuntimeException( 'Failed to create CSV file: ' . $file );
		}

		return $file;
	}

	/**
	 * Writes the header row to a CSV file.
	 *
	 * Default implementation does nothing because headers
	 * are written during the first append call.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file_path Absolute path to the file.
	 */
	public function write_header( string $file_path ): void {
		// Headers are handled during the first append_row call.
	}

	/**
	 * Appends a data row to the CSV file.
	 *
	 * If the file is empty, writes a header row using array keys.
	 *
	 * @since 0.1.0
	 *
	 * @param string   $file_path Absolute file path.
	 * @param string[] $row       Associative array representing a CSV row.
	 */
	public function append_row( string $file_path, array $row ): void {
		$decoded_row = array_map(
			static function ( $value ) {
				return is_string( $value ) ? html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : $value;
			},
			$row
		);

		// Read existing content.
		$content  = $this->fs->get_contents( $file_path );
		$is_empty = empty( $content );

		/**
		 * Use php://temp for in-memory buffering of CSV data.
		 *
		 * This avoids touching the real filesystem and ensures compatibility across
		 * environments. We use fputcsv to generate well-formatted lines, which would
		 * otherwise require unsafe escaping logic if concatenated manually.
		 *
		 * Although WordPress Coding Standards warn against fopen/fclose,
		 * this stream is fully memory-backed and safe to use.
		 */
		$fp = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( $is_empty ) {
			fputcsv( $fp, array_keys( $decoded_row ), ',', '"', '\\' );
		}

		fputcsv( $fp, array_values( $decoded_row ), ',', '"', '\\' );
		rewind( $fp );

		$new_data = stream_get_contents( $fp );

		/**
		 * Closes the temporary in-memory stream used for CSV formatting.
		 *
		 * Safe to ignore PHPCS warning because no real file I/O occurs.
		 */
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$existing = $is_empty ? '' : $this->fs->get_contents( $file_path );
		$this->fs->put_contents( $file_path, $existing . $new_data, FS_CHMOD_FILE );
	}

	/**
	 * Generates a public URL to download the given file.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file_path Absolute file path.
	 * @return string Download URL.
	 */
	public function generate_url( string $file_path ): string {
		$upload_dir = wp_upload_dir();
		$relative   = str_replace( $upload_dir['basedir'], '', $file_path );

		return trailingslashit( $upload_dir['baseurl'] ) . ltrim( $relative, '/' );
	}
}
