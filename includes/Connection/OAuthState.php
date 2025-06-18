<?php
/**
 * Utility class for encoding and decoding OAuth state payloads for Ad Partner connections.
 *
 * This helper class ensures safe transport of state data between redirect steps
 * in the OAuth authorization flow, using Base64 and URL-safe encoding.
 *
 * @package SnapchatForWooCommerce\Connection
 */

namespace SnapchatForWooCommerce\Connection;

use WP_Error;

/**
 * Handles encoding and decoding of state information used during OAuth authorization for Ad Partner services.
 *
 * The OAuth `state` parameter is a critical component in redirect flows to protect against CSRF attacks
 * and to persist context (e.g., return URL, service name) across redirects.
 *
 * This class safely serializes state data to a URL-safe format for inclusion in query strings and decodes it
 * back into structured arrays.
 */
final class OAuthState {
	/**
	 * Encodes an associative array into a URL-safe base64 string for use as an OAuth `state` parameter.
	 *
	 * The input array is serialized to JSON, base64 encoded, and then URL encoded.
	 *
	 * @since 0.1.0
	 *
	 * @param array $data Associative array of state data (e.g., return URL, service name).
	 *
	 * @return string URL-safe encoded state string.
	 */
	public static function encode( array $data ): string {
		 // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return rawurlencode( base64_encode( wp_json_encode( $data ) ) );
	}

	/**
	 * Decodes a URL-safe encoded OAuth `state` string back into an associative array.
	 *
	 * Performs raw URL decoding, base64 decoding, and JSON deserialization. If decoding fails,
	 * returns a `WP_Error` indicating the issue.
	 *
	 * @since 0.1.0
	 *
	 * @param string $state Encoded state string from the OAuth redirect.
	 *
	 * @return array|WP_Error Decoded state data array or error if decoding fails.
	 */
	public static function decode( string $state ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$decoded = base64_decode( rawurldecode( $state ), true );
		$data    = json_decode( $decoded, true );

		if ( ! is_array( $data ) || json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'invalid_state', 'Could not decode OAuth state.' );
		}

		return $data;
	}
}
