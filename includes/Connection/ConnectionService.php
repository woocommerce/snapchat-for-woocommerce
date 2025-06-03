<?php
/**
 * Handles REST API routes and connection logic for Snapchat integration with WooCommerce.
 *
 * This service class registers REST endpoints and manages authentication and connection status
 * between the WooCommerce store and Snapchat services.
 *
 * @package SnapchatForWooCommerce\Connection
 */

namespace SnapchatForWooCommerce\Connection;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Service class to manage connection routes and operations for Snapchat integration.
 *
 * This class exposes a RESTful interface under a defined namespace, enabling the frontend
 * or third-party systems to query connection status, initiate OAuth authorization, and handle
 * authorization responses. It uses the `JetpackAuthenticator` to manage authentication
 * headers securely, and delegates core connection logic to `WcsClient`.
 *
 * Dependencies:
 * - {@see WcsClient} Handles the interaction with WooCommerce Snapchat backend APIs.
 * - {@see JetpackAuthenticator} Provides authentication token (e.g. via Jetpack connection).
 * - {@see OAuthState} Decodes the OAuth `state` parameter passed during authorization flow.
 *
 * @see \SnapchatForWooCommerce\Connection\WcsClient
 * @see \SnapchatForWooCommerce\Connection\JetpackAuthenticator
 * @see \SnapchatForWooCommerce\Connection\OAuthState
 */
final class ConnectionService {
	/**
	 * The WCS client instance responsible for backend API communication.
	 *
	 * @var WcsClient
	 */
	private $wcs_client;

	/**
	 * Authenticator used to generate secure auth headers for API requests.
	 *
	 * @var JetpackAuthenticator
	 */
	private $authenticator;

	/**
	 * REST API namespace under which all routes will be registered.
	 *
	 * @var string
	 */
	private string $rest_namespace;

	/**
	 * Constructor for the connection service.
	 *
	 * @param WcsClient            $wcs_client     Dependency for backend communication.
	 * @param JetpackAuthenticator $authenticator  Handles authentication headers.
	 * @param string               $rest_namespace      REST namespace to register endpoints under.
	 */
	public function __construct( WcsClient $wcs_client, JetpackAuthenticator $authenticator, string $rest_namespace ) {
		$this->wcs_client     = $wcs_client;
		$this->authenticator  = $authenticator;
		$this->rest_namespace = $rest_namespace;
	}

	/**
	 * Registers REST routes related to the Snapchat connection.
	 *
	 * Each route maps to a public method in this class and exposes functionality like:
	 * - Checking connection status
	 * - Starting a new OAuth connection
	 * - Handling the authorization redirect
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->rest_namespace,
			'/connection/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->rest_namespace,
			'/connection/connect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'post_connect' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->rest_namespace,
			'/connection/authorize',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_authorize_redirect' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Checks the current Ad Partner's connection status.
	 *
	 * Retrieves the token via the authenticator and queries the WCS client.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Response with connection status or error if authentication fails.
	 */
	public function get_status() {
		$token = $this->authenticator->get_auth_header();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		return $this->wcs_client->get_connection_status( $token );
	}

	/**
	 * Starts the OAuth connection flow with the Ad Partner.
	 *
	 * Accepts an optional `returnUrl` parameter that defines the post-auth redirect.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object containing the return URL (optional).
	 *
	 * @return WP_REST_Response|WP_Error Response with connection data or an error if authentication fails.
	 */
	public function post_connect( WP_REST_Request $request ) {
		$token = $this->authenticator->get_auth_header();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$return_url = esc_url_raw( $request->get_param( 'returnUrl' ) ?? admin_url() );
		return $this->wcs_client->start_connection( $token, $return_url );
	}

	/**
	 * Handles redirect response from the Ad Partner after OAuth authorization.
	 *
	 * Uses the `state` parameter to validate and reconstruct the original request context.
	 * Returns a redirect response to the originating service or shows a failure.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object with query parameters (`state`, `code`, `error`).
	 *
	 * @return WP_REST_Response|WP_Error Redirect response or error if state is missing or invalid.
	 */
	public function handle_authorize_redirect( WP_REST_Request $request ) {
		$state_param = $request->get_param( 'state' );
		$code        = $request->get_param( 'code' );
		$error       = $request->get_param( 'error' );

		if ( empty( $state_param ) ) {
			return new WP_Error( 'missing_state', 'Missing state parameter.' );
		}

		$state = OAuthState::decode( $state_param );

		if ( is_wp_error( $state ) ) {
			return $state;
		}

		$return_url = $state['returnUrl'] ?? admin_url();
		$service    = $state['service'] ?? 'snapchat-ads';

		$query_arg = $error
			? array( $service => 'failed' )
			: array( $service => 'connected' );

		$redirect_url = add_query_arg( $query_arg, $return_url );

		return new WP_REST_Response(
			array(
				'redirect' => $redirect_url,
			),
			302
		);
	}
}
