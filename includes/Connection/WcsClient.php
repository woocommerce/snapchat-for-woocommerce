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
use SnapchatForWooCommerce\Utils\Helper;

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
	 * Sends a GET request to the WCS `/connection/status` endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $jetpack_token Jetpack authorization token.
	 *
	 * @return WP_REST_Response|WP_Error Connection status or error.
	 */
	public function get_connection_status( string $jetpack_token ) {
		return $this->proxy_get( $jetpack_token, 'connection/status' );
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
		return $this->proxy_get( $jetpack_token, 'connection/connect', array( 'returnUrl' => $return_url ) );
	}

	/**
	 * Sends a GET request to the WCS `/connection` endpoint to disconnect the Ad Partner.
	 *
	 * @since 0.1.0
	 *
	 * @param string $jetpack_token Jetpack authorization token.
	 *
	 * @return WP_REST_Response|WP_Error Disconnection response or error.
	 */
	public function stop_connection( string $jetpack_token ) {
		return $this->proxy_get( $jetpack_token, 'connection/disconnect' );
	}

	/**
	 * Sends a proxy GET request to an arbitrary WCS endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $token   Jetpack authorization token.
	 * @param string $path    Path within the WCS API (relative to service base).
	 *
	 * @return WP_REST_Response|WP_Error API response or error.
	 */
	public function proxy_get( string $token, string $path ) {
		return $this->proxy_request( 'GET', $token, $path, null );
	}

	/**
	 * Sends a proxy POST request to an arbitrary WCS endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $token   Jetpack authorization token.
	 * @param string $path    Path within the WCS API (relative to service base).
	 * @param mixed  $body    Body payload to send in JSON format.
	 *
	 * @return WP_REST_Response|WP_Error API response or error.
	 */
	public function proxy_post( string $token, string $path, $body ) {
		return $this->proxy_request( 'POST', $token, $path, $body );
	}

	/**
	 * Returns the base URL for the WCS endpoint.
	 *
	 * @return string
	 */
	private function get_wcs_url(): string {
		/**
		 * Filters the base URL for the WCS (Web Conversion Service) endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url The default WCS endpoint URL.
		 */
		return apply_filters(
			Helper::with_prefix( 'wcs_base_url' ),
			'https://api.woocommerce.com'
		);
	}

	/**
	 * The service name under which the Ad Partner integration is
	 * registered with WCS.
	 *
	 * @return string
	 */
	private function get_wcs_service_name(): string {
		/**
		 * Filters the service name used for the Ad Partner integration.
		 *
		 * @since 1.0.0
		 *
		 * @param string $service_name The default service name.
		 */
		return apply_filters(
			Helper::with_prefix( 'service_name' ),
			'snapchat'
		);
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
	 *
	 * @return WP_REST_Response|WP_Error Parsed response or error.
	 */
	private function proxy_request( string $method, string $jetpack_token, string $path, $body = null ) {
		$url = sprintf(
			'%s/%s/%s',
			$this->get_wcs_url(),
			$this->get_wcs_service_name(),
			ltrim( $path, '/' )
		);

		$args = array(
			'method'  => strtoupper( $method ),
			'timeout' => 15,
		);

		if ( $jetpack_token ) {
			$args['headers']['Authorization'] = $jetpack_token;
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
