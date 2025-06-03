<?php

namespace SnapchatForWooCommerce\Connection;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

final class ConnectionService {

	private $wcs_client;
	private $authenticator;

	public function __construct( WcsClient $wcs_client, JetpackAuthenticator $authenticator ) {
		$this->wcs_client     = $wcs_client;
		$this->authenticator  = $authenticator;
	}

	public function register_routes() {
		register_rest_route( 'snapchat-ads/v1', '/connection/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_status' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'snapchat-ads/v1', '/connection/connect', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'post_connect' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'snapchat-ads/v1', '/connection/authorize', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle_authorize_redirect' ],
			'permission_callback' => '__return_true',
		] );
	}

	public function get_status() {
		$token = $this->authenticator->get_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		return $this->wcs_client->get_connection_status( $token );
	}

	public function post_connect( WP_REST_Request $request ) {
		$token = $this->authenticator->get_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$return_url = esc_url_raw( $request->get_param( 'returnUrl' ) ?? admin_url() );
		return $this->wcs_client->start_connection( $token, $return_url );
	}

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
			? [ $service => 'failed' ]
			: [ $service => 'connected' ];

		$redirect_url = add_query_arg( $query_arg, $return_url );

		return new WP_REST_Response( [
			'redirect' => $redirect_url,
		], 302 );
	}
}
