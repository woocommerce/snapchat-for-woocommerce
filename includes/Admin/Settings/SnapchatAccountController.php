<?php
/**
 * REST controller for managing the Snapchat Ads OAuth connection.
 *
 * This controller handles the Jetpack-authenticated connection flow with
 * WooCommerce Connect Server (WCS) for Snapchat Ads.
 *
 * It supports three endpoints:
 * - POST   /connect     – Start the Snapchat OAuth flow.
 * - GET    /connection  – Check current Snapchat connection status.
 * - DELETE /connection  – Disconnect the Snapchat account.
 *
 * @package SnapchatForWooCommerce\Admin\Settings
 */

namespace SnapchatForWooCommerce\Admin\Settings;

use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;

/**
 * Controller for managing the Snapchat Ads account connection.
 *
 * @since 0.1.0
 */
class SnapchatAccountController extends SettingsBaseController {

	/**
	 * WCS proxy request client.
	 *
	 * @var WcsClient
	 */
	protected WcsClient $wcs;

	/**
	 * Authenticator for generating secure headers.
	 *
	 * @var JetpackAuthenticator
	 */
	private JetpackAuthenticator $auth;

	/**
	 * Constructor.
	 *
	 * @param WcsClient            $wcs  WCS proxy request client.
	 * @param JetpackAuthenticator $auth Authenticator for Jetpack headers.
	 */
	public function __construct( WcsClient $wcs, JetpackAuthenticator $auth ) {
		$this->wcs       = $wcs;
		$this->auth      = $auth;
		$this->namespace = 'wc/sfw/snapchat';
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'connect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'initiate_oauth' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'connection',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'check_connection' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_connection' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Starts the OAuth flow.
	 *
	 * @return WP_REST_Response
	 */
	public function initiate_oauth() {
		$token      = $this->auth->get_auth_header();
		$return_url = admin_url( 'admin.php?page=wc-admin&path=/snapchat/setup' );

		$response = $this->wcs->start_connection( $token, $return_url );

		if ( is_wp_error( $response ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
				)
			);
		}

		$data = $response->get_data();

		if ( empty( $data['oauthUrl'] ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => __( 'Invalid response from WCS. OAuth URL missing.', 'snapchat-for-woocommerce' ),
				)
			);
		}

		return rest_ensure_response(
			array( 'url' => esc_url_raw( $data['oauthUrl'] ) )
		);
	}

	/**
	 * Checks connection status.
	 *
	 * @return WP_REST_Response
	 */
	public function check_connection() {
		$token = $this->auth->get_auth_header();

		if ( empty( $token ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => __( 'Missing Jetpack authorization token.', 'snapchat-for-woocommerce' ),
				)
			);
		}

		$response = $this->wcs->get_connection_status( $token );

		if ( is_wp_error( $response ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
				)
			);
		}

		$data = $response->get_data();

		return rest_ensure_response(
			array(
				'status' => 'success',
				'email'  => $data['email'] ?? '',
			)
		);
	}

	/**
	 * Disconnects the merchant account.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_connection() {
		$token = $this->auth->get_auth_header();

		if ( empty( $token ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => __( 'Missing Jetpack authorization token.', 'snapchat-for-woocommerce' ),
				)
			);
		}

		$response = $this->wcs->stop_connection( $token );

		if ( is_wp_error( $response ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
				)
			);
		}

		$data = $response->get_data();

		return rest_ensure_response(
			array(
				'status' => 'success',
				'email'  => $data['email'] ?? '',
			)
		);
	}
}
