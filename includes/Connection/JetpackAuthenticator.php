<?php
/**
 * Jetpack-based authentication for Ad Partner connection.
 *
 * This class handles the generation of secure authentication headers using Jetpack tokens
 * for authorized communication with WooCommerce Services (WCS).
 *
 * @package SnapchatForWooCommerce\Connection
 */

namespace SnapchatForWooCommerce\Connection;

use Automattic\Jetpack\Connection\Manager;
use SnapchatForWooCommerce\Utils\Helper;
use Jetpack_Options;
use WP_Error;

/**
 * Provides Jetpack authentication headers for use with Ad Partner connection services.
 *
 * This class uses Jetpack's connection manager to retrieve access tokens, from which it derives
 * a secure signature using HMAC-SHA1 to comply with WCS authentication requirements.
 */
class JetpackAuthenticator {
	/**
	 * Jetpack Connection Manager instance.
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Constructor.
	 *
	 * Initializes the Jetpack connection manager used to retrieve access tokens.
	 */
	public function __construct() {
		$this->manager = new Manager();
	}

	/**
	 * Returns the Jetpack authentication header string for use in HTTP requests to WCS.
	 *
	 * Attempts to retrieve a token via the `ad_partner_jetpack_auth_token` filter, falling back
	 * to Jetpack’s internal token mechanism if the filter returns nothing.
	 *
	 * If token retrieval or structure validation fails, returns a `WP_Error`.
	 *
	 * @since 0.1.0
	 *
	 * @return string|WP_Error Authentication header string (e.g., "X_JP_Auth ...") or error.
	 */
	public function get_auth_header() {
		/**
		 * Filter to override or short-circuit the Jetpack authentication token retrieval.
		 *
		 * This filter allows developers (e.g., during automated tests or custom integrations)
		 * to inject a mock or predefined authentication token, bypassing the default logic
		 * in {@see JetpackAuthenticator::get_auth_header()}.
		 *
		 * Example use case: mocking the auth header during integration or unit testing.
		 *
		 * @since 0.1.0
		 *
		 * @param string|null $token The token to use for authenticated requests. Default null (uses default auth method).
		 */
		$token = apply_filters( Helper::with_prefix( 'jetpack_auth_token' ), null );

		if ( $token ) {
			return $token;
		}

		// Get Jetpack access token object from connection manager.
		$token = $this->manager->get_tokens()->get_access_token();

		if ( ! $token || ! isset( $token->secret, $token->external_user_id ) ) {
			return new WP_Error( 'missing_jetpack_token', __( 'Jetpack token is not available.', 'snapchat-for-woocommerce' ) );
		}

		$parts = explode( '.', $token->secret );
		if ( count( $parts ) !== 2 ) {
			return new WP_Error( 'invalid_jetpack_token', __( 'Invalid Jetpack token format.', 'snapchat-for-woocommerce' ) );
		}

		list( $token_key_raw, $token_secret ) = $parts;

		$token_key = sprintf(
			'%s:%d:%d',
			$token_key_raw,
			defined( 'JETPACK__API_VERSION' ) ? JETPACK__API_VERSION : 1,
			$token->external_user_id
		);

		// Generate secure metadata for signature.
		$time_diff = (int) Jetpack_Options::get_option( 'time_diff' );
		$timestamp = time() + $time_diff;
		$nonce     = wp_generate_password( 10, false );

		// Construct signature base string.
		$normalized_string = $token_key . "\n" . $timestamp . "\n" . $nonce . "\n";

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$signature = base64_encode( hash_hmac( 'sha1', $normalized_string, $token_secret, true ) );

		$auth = array(
			'token'     => $token_key,
			'timestamp' => $timestamp,
			'nonce'     => $nonce,
			'signature' => $signature,
		);

		// Assemble into header string format.
		$header_pieces = array();
		foreach ( $auth as $key => $value ) {
			$header_pieces[] = sprintf( '%s="%s"', $key, $value );
		}

		return 'X_JP_Auth ' . implode( ' ', $header_pieces );
	}
}
