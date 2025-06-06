<?php
/**
 * Integration tests for the ConnectionService class (REST API layer).
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Connection
 */

namespace SnapchatForWooCommerce\Tests\Integration\Connection;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_Error;
use SnapchatForWooCommerce\Connection\ConnectionService;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Connection\WcsClient;

/**
 * @covers \SnapchatForWooCommerce\Connection\ConnectionService
 */
class ConnectionServiceTest extends WP_UnitTestCase {

	/**
	 * The namespace used in the ConnectionService under test.
	 */
	private const REST_NAMESPACE = 'snapchat-ads/v1'; // Adjust to your namespace.

	/**
	 * Service under test.
	 *
	 * @var ConnectionService
	 */
	private $service;

	/**
	 * Mocks.
	 */
	private $auth_mock;
	private $wcs_client_mock;

	public function set_up(): void {
		parent::set_up();

		$this->auth_mock       = $this->createMock( JetpackAuthenticator::class );
		$this->wcs_client_mock = $this->createMock( WcsClient::class );

		$this->service = new ConnectionService(
			$this->wcs_client_mock,
			$this->auth_mock,
			self::REST_NAMESPACE
		);

		// Create REST server.
		$GLOBALS['wp_rest_server'] = new \WP_REST_Server();

		// Register the routes.
		add_action( 'rest_api_init', array( $this->service, 'register_routes' ) );

		// Trigger rest_api_init.
		do_action( 'rest_api_init' );
	}

	/**
	 * Test GET /connection/status returns connected status.
	 */
	public function test_get_status_returns_connected() {
		$this->auth_mock->method( 'get_auth_header' )->willReturn( 'Bearer token' );

		$this->wcs_client_mock->method( 'get_connection_status' )->willReturn(
			array(
				'status' => 'connected',
				'email'  => 'user@example.com',
			)
		);

		$request  = new WP_REST_Request( 'GET', '/' . self::REST_NAMESPACE . '/connection/status' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'connected', $response->get_data()['status'] );
		$this->assertEquals( 'user@example.com', $response->get_data()['email'] );
	}

	/**
	 * Test POST /connection/connect returns oauthUrl.
	 */
	public function test_post_connect_returns_oauth_url() {
		$this->auth_mock->method( 'get_auth_header' )->willReturn( 'Bearer token' );

		$this->wcs_client_mock->method( 'start_connection' )
			->with( 'Bearer token', $this->callback( function ( $url ) {
				return is_string( $url ); // ensure it's a URL
			} ) )
			->willReturn(
				array(
					'oauthUrl' => 'https://snapchat.oauth.example.com',
				)
			);

		$request = new WP_REST_Request( 'POST', '/' . self::REST_NAMESPACE . '/connection/connect' );
		$request->set_param( 'returnUrl', 'https://example.com/return' );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'https://snapchat.oauth.example.com', $response->get_data()['oauthUrl'] );
	}

	/**
	 * Test GET /connection/status returns error if authentication fails.
	 */
	public function test_get_status_returns_error_on_auth_failure() {
		$this->auth_mock->method( 'get_auth_header' )->willReturn(
			new WP_Error( 'auth_failed', 'Authentication failed.' )
		);

		$request  = new WP_REST_Request( 'GET', '/' . self::REST_NAMESPACE . '/connection/status' );
		$response = rest_do_request( $request );

		$this->assertEquals( 500, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'auth_failed', $data['code'] );
		$this->assertEquals( 'Authentication failed.', $data['message'] );
	}

	/**
	 * Test GET /connection/authorize builds correct redirect URL.
	 */
	public function test_handle_authorize_redirect_builds_redirect_url() {
		// Provide a valid encoded state.
		$state_array = array(
			'returnUrl' => 'https://example.com/success',
			'service'   => 'snapchat-ads',
		);
		$encoded_state = base64_encode( wp_json_encode( $state_array ) );

		$request = new WP_REST_Request( 'GET', '/' . self::REST_NAMESPACE . '/connection/authorize' );
		$request->set_param( 'state', $encoded_state );

		$response = $this->service->handle_authorize_redirect( $request );

		$this->assertEquals( 302, $response->get_status() );
		$this->assertArrayHasKey( 'redirect', $response->get_data() );
		$this->assertStringContainsString( 'snapchat-ads=connected', $response->get_data()['redirect'] );
	}
}
