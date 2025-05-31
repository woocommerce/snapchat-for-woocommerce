<?php

namespace SnapchatForWoocommerce\Infrastructure;

use Automattic\Jetpack\Connection\Manager;
use Jetpack_Options;

class JetpackAuthenticator {
	private Manager $manager;

	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	public function get_auth_header(): string {
		$token = $this->manager->get_tokens()->get_access_token();

		if ( ! $token || ! isset( $token->secret ) ) {
			throw new \RuntimeException( 'Jetpack token is not available.' );
		}

		$parts = explode( '.', $token->secret );
		if ( count( $parts ) !== 2 ) {
			throw new \RuntimeException( 'Invalid Jetpack token format.' );
		}

		list( $token_key_raw, $token_secret ) = $parts;

		$token_key = sprintf(
			'%s:%d:%d',
			$token_key_raw,
			defined( 'JETPACK__API_VERSION' ) ? JETPACK__API_VERSION : 1,
			$token->external_user_id
		);

		$time_diff = (int) Jetpack_Options::get_option( 'time_diff' );
		$timestamp = time() + $time_diff;
		$nonce     = wp_generate_password( 10, false );

		$normalized_string = $token_key . "\n" . $timestamp . "\n" . $nonce . "\n";
		$signature         = base64_encode( hash_hmac( 'sha1', $normalized_string, $token_secret, true ) );

		$auth = [
			'token'     => $token_key,
			'timestamp' => $timestamp,
			'nonce'     => $nonce,
			'signature' => $signature,
		];

		$header_pieces = [];
		foreach ( $auth as $key => $value ) {
			$header_pieces[] = sprintf( '%s="%s"', $key, $value );
		}

		return 'X_JP_Auth ' . implode( ' ', $header_pieces );
	}
}
