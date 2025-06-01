<?php

namespace SnapchatForWoocommerce\API;

use SnapchatForWoocommerce\Config\AdPartnerConfigInterface;
use SnapchatForWoocommerce\Infrastructure\WcsClient;
use SnapchatForWoocommerce\Infrastructure\JetpackAuthenticator;

final class ConnectionService {

	private AdPartnerConfigInterface $config;
	private WcsClient $wcs;
	private JetpackAuthenticator $auth;

	public function __construct( AdPartnerConfigInterface $config, WcsClient $wcs, JetpackAuthenticator $auth ) {
		$this->config = $config;
		$this->wcs    = $wcs;
		$this->auth   = $auth;
	}

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

	public function connect() {
		$url = $this->wcs->get_url_for( 'snapchat/connection/' . $this->config->get_service_slug() );

		$response = wp_remote_post( $url, [
			'headers' => [
				// 'X_JP_Auth'    => $this->auth->get_auth_header(), // TODO: Use this one.
				'X_JP_Auth'    => '',
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

	public function get_status() {
		$url = $this->wcs->get_url_for( 'snapchat/connection/' . $this->config->get_service_slug() );

		$response = wp_remote_get( $url, [
			'headers' => [
				// 'X_JP_Auth' => $this->auth->get_auth_header(), // TODO: Use this one.
				'X_JP_Auth' => '', // mocking it.
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return rest_ensure_response( json_decode( wp_remote_retrieve_body( $response ), true ) );

	}

	public function can_manage(): bool {
		return true;
		return current_user_can( 'manage_woocommerce' );
	}
}
