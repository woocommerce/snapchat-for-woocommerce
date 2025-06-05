<?php
/**
 * Tests for the OAuthState utility class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Connection
 */

namespace SnapchatForWooCommerce\Tests\Integration\Connection;

use WP_Error;
use WP_UnitTestCase;
use SnapchatForWooCommerce\Connection\OAuthState;

class OAuthStateTest extends WP_UnitTestCase {

	/**
	 * Tests that encoding and decoding returns the original array.
	 */
	public function test_encode_and_decode_round_trip_returns_original_data() {
		$data = array(
			'return_url' => 'https://example.com/callback',
			'site_id'    => 123,
			'token'      => 'abc123',
		);

		$encoded = OAuthState::encode( $data );
		$decoded = OAuthState::decode( $encoded );

		$this->assertIsArray( $decoded );
		$this->assertSame( $data, $decoded );
	}

	/**
	 * Tests that decode returns a WP_Error for malformed state input.
	 */
	public function test_decode_returns_wp_error_for_invalid_data() {
		$invalid_encoded = '%E0%A4%A'; // Invalid rawurlencoded base64 string.

		$result = OAuthState::decode( $invalid_encoded );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_state', $result->get_error_code() );
	}

	/**
	 * Tests that decode returns a WP_Error for valid base64 but invalid JSON.
	 */
	public function test_decode_returns_wp_error_for_non_json_base64() {
		$not_json = base64_encode( 'not a json string' );
		$encoded  = rawurlencode( $not_json );

		$result = OAuthState::decode( $encoded );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_state', $result->get_error_code() );
	}
}
