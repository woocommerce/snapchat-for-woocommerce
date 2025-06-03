<?php

namespace SnapchatForWooCommerce\Connection;

use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

final class WcsClient {

	const WCS_BASE_URL = 'https://wcs-mock.mylocal/wp-json/mock-wcs';
	const SERVICE_NAME = 'snapchat';

	public function get_connection_status( string $jetpack_token ) {
		return $this->proxy_request( 'GET', $jetpack_token, 'connection/status' );
	}

	public function start_connection( string $jetpack_token, string $return_url ) {
		return $this->proxy_request( 'POST', $jetpack_token, 'connection/connect', [ 'returnUrl' => $return_url ] );
	}

	public function proxy_get( string $token, string $path ) {
		return $this->proxy_request( 'GET', $token, $path );
	}

	public function proxy_post( string $token, string $path, $body ) {
		return $this->proxy_request( 'POST', $token, $path, $body );
	}

	private function proxy_request( string $method, string $jetpack_token, string $path, $body = null ) {
		$url = sprintf( '%s/%s/%s', self::WCS_BASE_URL, self::SERVICE_NAME, ltrim( $path, '/' ) );

		$args = [
			'method'  => strtoupper( $method ),
			'timeout' => 15,
			'headers' => [
				'Authorization' => "Bearer $jetpack_token",
			],
		];

		if ( $body !== null ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		return $this->handle_response( $response );
	}

	private function handle_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			return new WP_REST_Response( $body, $code );
		}

		return new WP_Error(
			'wcs_error',
			__( 'WCS request failed', 'snapchat-for-woocommerce' ),
			[ 'response' => $response ]
		);
	}
}
