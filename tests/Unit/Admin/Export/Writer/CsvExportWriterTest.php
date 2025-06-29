<?php
/**
 * Unit tests for the CsvExportWriter class.
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\Export\Writer;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Admin\Export\Writer\CsvExportWriter;

/**
 * @covers \SnapchatForWooCommerce\Admin\Export\Writer\CsvExportWriter
 */
class CsvExportWriterTest extends WP_UnitTestCase {

	/**
	 * CsvExportWriter instance.
	 *
	 * @var CsvExportWriter
	 */
	private $writer;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->writer = new CsvExportWriter();
	}

	/**
	 * Tests that create_file() successfully creates a file in the expected directory.
	 */
	public function test_create_file_creates_csv_file() {
		$file = $this->writer->create_file();

		$this->assertFileExists( $file );
		$this->assertStringEndsWith( '.csv', $file );

		// Clean up
		unlink( $file );
	}

	/**
	 * Tests that append_row() correctly writes CSV header and row.
	 */
	public function test_append_row_writes_valid_csv_line() {
		$file = $this->writer->create_file();

		$data = array(
			'id'           => 123,
			'title'        => 'Test Product',
			'price'        => '49.99 USD',
			'availability' => 'in stock',
		);

		$this->writer->append_row( $file, $data );

		$handle = fopen( $file, 'r' );
		$header = fgetcsv( $handle );
		$row    = fgetcsv( $handle );
		fclose( $handle );

		$this->assertSame( array_keys( $data ), $header );
		$this->assertSame( array_map( 'strval', array_values( $data ) ), $row );

		// Clean up
		unlink( $file );
	}

	/**
	 * Tests that generate_url() returns a valid download URL based on file path.
	 */
	public function test_generate_url_returns_valid_download_link() {
		$file = $this->writer->create_file();
		$url  = $this->writer->generate_url( $file );

		$this->assertStringContainsString( '/wp-content/uploads/snapchat-exports/', $url );
		$this->assertStringEndsWith( '.csv', $url );

		// Clean up
		unlink( $file );
	}
}
