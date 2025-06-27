<?php
/**
 * Client for interacting with the Ad Partner's WooCommerce Services (WCS) API.
 *
 * This class provides methods to check connection status, start a connection,
 * and proxy GET/POST requests to the WCS backend on behalf of the Ad Partner integration.
 *
 * @package SnapchatForWooCommerce\Connection
 */

namespace SnapchatForWooCommerce\Connection;

use WP_REST_Response;
use WP_Error;

/**
 * Performs authenticated API calls to the Ad Partner's WCS backend.
 *
 * The WCS backend is a Jetpack-authenticated service that supports
 * OAuth-secured endpoints such as `/connection/status` and `/connection/connect`.
 *
 * This class includes helpers to send GET and POST requests using Jetpack tokens.
 * It is designed to centralize all HTTP communication between the plugin and the remote WCS service.
 */
final class WcsClient {
	/**
	 * Base URL for the WCS endpoint.
	 */
	const WCS_BASE_URL = 'https://wcs-mock.mylocal/wp-json/mock-wcs';

	/**
	 * The service name under which the Ad Partner integration is registered.
	 */
	const SERVICE_NAME = 'snapchat';

	/**
	 * Sends a GET request to the WCS `/connection/status` endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $jetpack_token Jetpack authorization token.
	 *
	 * @return WP_REST_Response|WP_Error Connection status or error.
	 */
	public function get_connection_status( string $jetpack_token ) {
		return $this->proxy_request( 'GET', $jetpack_token, 'connection/status' );
	}

	/**
	 * Sends a POST request to the WCS `/connection/connect` endpoint to initiate a connection.
	 *
	 * @since 0.1.0
	 *
	 * @param string $jetpack_token Jetpack authorization token.
	 * @param string $return_url    URL to redirect the user to after authorization.
	 *
	 * @return WP_REST_Response|WP_Error Connection initiation response or error.
	 */
	public function start_connection( string $jetpack_token, string $return_url ) {
		return $this->proxy_request( 'POST', $jetpack_token, 'connection/connect', array( 'returnUrl' => $return_url ) );
	}

	/**
	 * Sends a proxy GET request to an arbitrary WCS endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $token   Jetpack authorization token.
	 * @param string $path    Path within the WCS API (relative to service base).
	 * @param string $service The target service name used in the WCS proxy path (e.g., 'ads' or 'conversions').
	 *
	 * @return WP_REST_Response|WP_Error API response or error.
	 */
	public function proxy_get( string $token, string $path, string $service = 'ads' ) {
		return $this->proxy_request( 'GET', $token, $path, null, $service );
	}

	/**
	 * Sends a proxy POST request to an arbitrary WCS endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $token   Jetpack authorization token.
	 * @param string $path    Path within the WCS API (relative to service base).
	 * @param mixed  $body    Body payload to send in JSON format.
	 * @param string $service The target service name used in the WCS proxy path (e.g., 'ads' or 'conversions').
	 *
	 * @return WP_REST_Response|WP_Error API response or error.
	 */
	public function proxy_post( string $token, string $path, $body, string $service = 'ads' ) {
		return $this->proxy_request( 'POST', $token, $path, $body, $service );
	}

	/**
	 * Internal method for executing a proxy request to the WCS API.
	 *
	 * Assembles the full URL, sets headers including the Jetpack token,
	 * and sends the request using `wp_remote_request()`.
	 *
	 * @since 0.1.0
	 *
	 * @param string     $method         HTTP method (`GET` or `POST`).
	 * @param string     $jetpack_token  Jetpack authorization token.
	 * @param string     $path           Endpoint path relative to the service root.
	 * @param array|null $body           Optional request body (for POST).
	 * @param string     $service        The target service name used in the WCS proxy path (e.g., 'ads' or 'conversions').
	 *
	 * @return WP_REST_Response|WP_Error Parsed response or error.
	 */
	private function proxy_request( string $method, string $jetpack_token, string $path, $body = null, string $service = 'ads' ) {
		$url = sprintf(
			'%s/%s/%s/%s',
			self::WCS_BASE_URL,
			self::SERVICE_NAME,
			rawurlencode( $service ),
			ltrim( $path, '/' )
		);

		$args = array(
			'method'  => strtoupper( $method ),
			'timeout' => 15,
		);

		if ( $jetpack_token ) {
			$args['headers']['Authorization'] = "Bearer $jetpack_token";
		}

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		return $this->handle_response( $response );
	}

	/**
	 * Parses a WCS API response and returns a formatted result or error.
	 *
	 * If the response is a valid 2xx response, returns a `WP_REST_Response`.
	 * Otherwise, wraps the raw response inside a `WP_Error` object.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $response The result from `wp_remote_request()`.
	 *
	 * @return WP_REST_Response|WP_Error REST response object or error.
	 */
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
			array( 'response' => $response )
		);
	}
}
