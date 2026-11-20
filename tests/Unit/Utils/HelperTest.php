<?php
/**
 * Tests for the Helper utility class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Utils
 */

namespace SnapchatForWooCommerce\Tests\Unit\Utils;

use WP_Error;
use WP_UnitTestCase;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * @covers \SnapchatForWooCommerce\Utils\Helper
 */
class HelperTest extends WP_UnitTestCase {

	/**
	 * Test that the HTTP status is read from each shape of WP_Error the WCS client returns.
	 *
	 * @dataProvider error_status_provider
	 *
	 * @param WP_Error $error           Error returned by the WCS client.
	 * @param int|null $expected_status Expected HTTP status.
	 */
	public function test_get_error_status_code( WP_Error $error, ?int $expected_status ): void {
		$this->assertSame( $expected_status, Helper::get_error_status_code( $error ) );
	}

	/**
	 * Errors the WCS client can return, with the HTTP status each carries.
	 *
	 * @return array
	 */
	public function error_status_provider(): array {
		return array(
			'transport error without data'   => array(
				new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ),
				null,
			),
			'sandbox block with status data' => array(
				new WP_Error( 'snapchat_sandbox_remote_request_blocked', 'Blocked', array( 'status' => 403 ) ),
				403,
			),
			'non-2xx raw response'           => array(
				new WP_Error(
					'snapchat_for_woocommerce_request_failed',
					'Request failed',
					array(
						'response' => array(
							'code'    => 503,
							'message' => 'Service Unavailable',
						),
						'body'     => '',
					)
				),
				503,
			),
		);
	}
}
