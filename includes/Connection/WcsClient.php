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
use Jetpack_Options;
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
	 * Authenticator used to generate secure auth headers for API requests.
	 *
	 * @var JetpackAuthenticator
	 */
	private JetpackAuthenticator $authenticator;

	/**
	 * Jetpack client wrapper.
	 *
	 * @var JetpackClient
	 */
	private JetpackClient $jetpack_client;

	/**
	 * Class construnctor.
	 *
	 * @param JetpackAuthenticator $authenticator  Authenticator for API access.
	 * @param JetpackClient        $jetpack_client Jetpack client wrapper.
	 */
	public function __construct( JetpackAuthenticator $authenticator, JetpackClient $jetpack_client ) {
		$this->authenticator  = $authenticator;
		$this->jetpack_client = $jetpack_client;
	}

	/**
	 * Sends a proxy GET request to an arbitrary WCS endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path          Path within the WCS API (relative to service base).
	 * @param array  $query_params  Optional query parameters to append to the URL.
	 * @param bool   $requires_auth Whether to include the Jetpack auth header.
	 *
	 * @return WP_REST_Response|WP_Error API response or error.
	 */
	public function proxy_get( string $path, array $query_params = array(), bool $requires_auth = true ) {
		return $this->proxy_request( 'GET', $path, $query_params, $requires_auth );
	}

	/**
	 * Sends a proxy POST request to an arbitrary WCS endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path           Path within the WCS API (relative to service base).
	 * @param mixed  $body           Body payload to send in JSON format.
	 * @param bool   $requires_auth  Whether to include the Jetpack auth header.
	 *
	 * @return WP_REST_Response|WP_Error API response or error.
	 */
	public function proxy_post( string $path, $body, bool $requires_auth = true ) {
		return $this->proxy_request( 'POST', $path, $body, $requires_auth );
	}

	/**
	 * Sends a proxy DELETE request to an arbitrary WCS endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path           Path within the WCS API (relative to service base).
	 * @param bool   $requires_auth  Whether to include the Jetpack auth header.
	 *
	 * @return WP_REST_Response|WP_Error API response or error.
	 */
	public function proxy_delete( string $path, bool $requires_auth = true ) {
		return $this->proxy_request( 'DELETE', $path, null, $requires_auth );
	}

	/**
	 * Returns the base URL for the WCS endpoint.
	 *
	 * @return string
	 */
	public function get_wcs_url(): string {
		/**
		 * Filters the base URL for the WCS (Web Conversion Service) endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url The default WCS endpoint URL.
		 */
		return apply_filters(
			Helper::with_prefix( 'wcs_base_url' ),
			sprintf(
				'https://public-api.wordpress.com/wpcom/v2/sites/%s/wc',
				Jetpack_Options::get_option( 'id' )
			)
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
	 * @param string     $path           Endpoint path relative to the service root.
	 * @param array|null $body           Optional request body (for POST) or query parameters (for GET).
	 * @param bool       $requires_auth  Whether to include the Jetpack auth header.
	 *
	 * @return WP_REST_Response|WP_Error Parsed response or error.
	 */
	public function proxy_request( string $method, string $path, $body = null, $requires_auth = true ) {
		$url = sprintf(
			'%s/%s/%s',
			$this->get_wcs_url(),
			$this->get_wcs_service_name(),
			ltrim( $path, '/' )
		);

		if ( 'GET' === strtoupper( $method ) && ! empty( $body ) && is_array( $body ) ) {
			$url = add_query_arg( $body, $url );
		}

		$args = array(
			'method'  => strtoupper( $method ),
			'timeout' => 15,
		);

		if ( $requires_auth ) {
			$jetpack_token = $this->authenticator->get_auth_header();

			if ( empty( $jetpack_token ) ) {
				return new WP_REST_Response( array( 'message' => 'Jetpack token missing' ) );
			}

			$args['headers']['Authorization'] = $jetpack_token;
		}

		if ( 'POST' === $method && ! empty( $body ) ) {
			$args['headers']['Content-Type'] = 'application/json';
		}

		$response = $this->jetpack_client->remote_request(
			array_merge(
				$args,
				array( 'url' => $url ),
			),
			'POST' === $method && $body ? wp_json_encode( $body ) : null
		);

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
			Helper::with_prefix( 'request_failed' ),
			__( 'Request failed', 'snapchat-for-woocommerce' ),
			$response
		);
	}
}
