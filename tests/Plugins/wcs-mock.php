<?php
/**
 * Plugin Name: Mock WCS for Snapchat
 * Description: Simulates WCS OAuth + Proxy endpoints for Snapchat Ads plugin development.
 * Version: 0.2.0
 * Author: Dev Sandbox
 */

static $snapchat_oauth_app = [
	'redirect_uri' => 'https://snapchat.mylocal',
	'access_token' => '', // Needs to be set.
];

add_action( 'rest_api_init', function () use ( $snapchat_oauth_app ) {
	// Connection status endpoint.
	register_rest_route( 'mock-wcs/snapchat', '/mock', [
		'methods'  => 'GET',
		'callback' => function () use ( $snapchat_oauth_app ) {
			return rest_ensure_response('wow, this works!');
		},
		'permission_callback' => '__return_true'
	] );

	// Authorize callback endpoint.
	register_rest_route( 'mock-wcs/snapchat', '/snapchat/authorize', [
		'methods'  => 'GET',
		'callback' => function () {
			return new WP_REST_Response( null, 302, [
				'Location' => add_query_arg([
					'snapchat' => 'connected'
				], home_url( '/' ))
			]);
		},
		'permission_callback' => '__return_true'
	] );

	// Connection disconnect endpoint.
	register_rest_route( 'mock-wcs/snapchat', '/snapchat/connection/snapchat-ads', [
		'methods'  => 'DELETE',
		'callback' => function () {
			delete_transient( 'mock_snapchat_connection_status' );
			return rest_ensure_response([ 'status' => 'disconnected' ]);
		},
		'permission_callback' => '__return_true'
	] );

	// Generic proxy passthrough to real Snapchat API.
	register_rest_route( 'mock-wcs/snapchat', '/(?P<proxy_path>.+)', [
		'methods'  => [ 'GET', 'POST', 'PUT', 'DELETE' ],
		'callback' => function ( WP_REST_Request $request ) use ( $snapchat_oauth_app ) {
			$params = $request->get_params();
			$path   = $request->get_param( 'proxy_path' );
			$method = $request->get_method();
			$token  = 'Bearer ' . $snapchat_oauth_app['access_token'];

			$url = 'https://adsapi.snapchat.com/v1/' . ltrim( $path, '/' );

			$args = [
				'headers' => [
					'Authorization' => $token,
					'Content-Type'  => 'application/json',
				],
				'method' => $method,
			];

			if ( in_array( $method, [ 'POST', 'PUT', 'PATCH' ], true ) ) {
				$args['body'] = wp_json_encode( $request->get_json_params() );
			}

			$response = wp_remote_request( $url, $args );
			$code     = wp_remote_retrieve_response_code( $response );
			$body     = wp_remote_retrieve_body( $response );
			$data     = json_decode( $body, true );

			return new WP_REST_Response( $data, $code );
		},
		'permission_callback' => '__return_true'
	] );
});
