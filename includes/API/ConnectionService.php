<?php

namespace SnapchatForWoocommerce\API;

use SnapchatForWoocommerce\Config\AdPartnerConfigInterface;
use SnapchatForWoocommerce\Infrastructure\WcsClient;
use SnapchatForWoocommerce\Infrastructure\JetpackAuthenticator;

final class ConnectionService {

	/**
	 * @var AdPartnerConfigInterface Configuration for REST namespace, service slug, etc.
	 */
	private AdPartnerConfigInterface $config;

	/**
	 * @var WcsClient Handles request routing to remote proxy endpoints.
	 */
	private WcsClient $wcs;

	/**
	 * @var JetpackAuthenticator Provides the `X_JP_Auth` secure header for remote calls.
	 */
	private JetpackAuthenticator $auth;

	/**
	 * ConnectionService constructor.
	 *
	 * @param AdPartnerConfigInterface $config Dynamic plugin-specific configuration provider.
	 * @param WcsClient $wcs Client to communicate with WooCommerce Services proxy API.
	 * @param JetpackAuthenticator $auth Provides secure authentication headers.
	 */
	public function __construct( AdPartnerConfigInterface $config, WcsClient $wcs, JetpackAuthenticator $auth ) {
		$this->config = $config;
		$this->wcs    = $wcs;
		$this->auth   = $auth;
	}

	/**
	 * Registers REST API routes for the Ad Partner's connection handling.
	 *
	 * This function sets up two endpoints:
	 * - POST `/connection/connect`: Initiates the remote connection setup.
	 * - GET  `/connection/status`: Retrieves current connection status.
	 *
	 * Each route is protected via `can_manage()` permission callback.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( $this->config->get_rest_namespace(), '/connection/connect', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'connect' ],
			'permission_callback' => [ $this, 'can_manage' ],
		] );

		register_rest_route( $this->config->get_rest_namespace(), '/connection/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_status' ],
			'permission_callback' => [ $this, 'can_manage' ],
		] );
	}

	/**
	 * Handles the connection request to the remote Ad Partner proxy service.
	 *
	 * Sends a secure POST request containing the return URL to the partner API.
	 * Expects JSON in response. Will return WP_Error on failure.
	 *
	 * @return WP_REST_Response|WP_Error A WP REST response or error object.
	 */
	public function connect() {
		$url = $this->wcs->get_url_for( 'snapchat/connection/' . $this->config->get_service_slug() );

		$response = wp_remote_post( $url, [
			'headers' => [
				'X_JP_Auth'    => $this->auth->get_auth_header(),
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode( [
				'returnUrl' => $this->config->get_return_url(),
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return rest_ensure_response( json_decode( wp_remote_retrieve_body( $response ), true ) );
	}

	/**
	 * Retrieves current connection status from the Ad Partner's proxy service.
	 *
	 * Sends a GET request to the connection status endpoint with a secure auth header.
	 * Returns either structured JSON or a WP_Error if the call fails.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_status() {
		$url = $this->wcs->get_url_for( 'snapchat/connection/' . $this->config->get_service_slug() );

		$response = wp_remote_get( $url, [
			'headers' => [
				'X_JP_Auth' => $this->auth->get_auth_header(),
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return rest_ensure_response( json_decode( wp_remote_retrieve_body( $response ), true ) );

	}

	/**
	 * Checks whether the current user has permission to manage the plugin integration.
	 *
	 * Currently returns `true` unconditionally. In a production environment, this should
	 * likely check `current_user_can( 'manage_woocommerce' )` or similar.
	 *
	 * @return bool True if user can manage; false otherwise.
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_woocommerce' );
	}
}
