<?php
/**
 * Integration test for SnapchatSnapPixelController using real REST API call (mocked via pre_http_request).
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Admin\Settings
 */

namespace SnapchatForWooCommerce\Tests\Integration\Admin\Settings;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * Tests the SnapchatSnapPixelController REST API endpoints.
 *
 * Validates behavior of pixel retrieval, selection, and deletion for a Snapchat Ad Account,
 * including transient management for caching the pixel script.
 *
 * @group rest-api
 */
class SnapchatSnapPixelControllerTest extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Path to mock pixels fixture file.
	 *
	 * @var string
	 */
	protected $pixels_fixture_response;

	/**
	 * Array of mocked option data.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$user_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $user_id );

		$this->server  = rest_get_server();
		$this->options = require __DIR__ . '/fixtures/options.php';

		Options::delete( OptionDefaults::ORGANIZATIONS );
		Options::delete( OptionDefaults::ORGANIZATION_ID );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		Options::delete( OptionDefaults::PIXELS );
		Options::delete( OptionDefaults::PIXEL_ID );

		add_filter( Helper::with_prefix( 'jetpack_auth_token' ), fn() => 'abc123' );

		$this->pixels_fixture_response = __DIR__ . '/fixtures/pixels.json';
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down(): void {
		parent::tear_down();
	}

	/**
	 * Intercept HTTP requests and return mock pixel data from fixture.
	 *
	 * @param mixed  $preempt     Preemptive response.
	 * @param array  $parsed_args Parsed request arguments.
	 * @param string $url         Request URL.
	 * @return array|mixed
	 */
	public function intercept_wcs_http_requests_with_non_empty_response( $preempt, $parsed_args, $url ) {
		if ( strpos( $url, '/ads/v1/adaccounts/294da428-5f98-4c78-9946-14cf65cff14b/pixels' ) !== false ) {
			return array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => file_get_contents( $this->pixels_fixture_response ),
			);
		}

		return $preempt;
	}

	/**
	 * Test: GET /pixels without ads_account_id returns parameter error.
	 */
	public function test_get_pixels_from_wcs_without_payload(): void {
		add_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_non_empty_response' ), 10, 3 );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/pixels' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_non_empty_response' ), 10 );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertSame( 'rest_missing_callback_param', $data['code'] );
		$this->assertSame( 'Missing parameter(s): ads_account_id', $data['message'] );
	}

	/**
	 * Test: GET /pixels fetches data from WCS and returns sanitized list.
	 */
	public function test_get_pixels_from_wcs_when_cache_empty(): void {
		add_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_non_empty_response' ), 10, 3 );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/pixels' );
		$request->set_query_params( array( 'ads_account_id' => '294da428-5f98-4c78-9946-14cf65cff14b' ) );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_non_empty_response' ), 10 );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertSame( $data, $this->options['pixels_sanitized'] );
	}

	/**
	 * Test: GET /pixels returns from cache if available.
	 */
	public function test_get_pixels_from_wcs_when_cache_exists(): void {
		Options::set( OptionDefaults::PIXELS, $this->options['pixels_sanitized'] );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/pixels' );
		$request->set_query_params( array( 'ads_account_id' => '294da428-5f98-4c78-9946-14cf65cff14b' ) );
		$response = $this->server->dispatch( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertSame( $data, $this->options['pixels_sanitized'] );
	}

	/**
	 * Test: POST /pixel sets valid pixel ID and caches script.
	 */
	public function test_set_pixel_id_with_correct_id(): void {
		Options::set( OptionDefaults::PIXELS, $this->options['pixels_sanitized'] );

		$request  = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/pixel' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode(
			array( 'id' => '6abc82ca-4a3a-4391-98ba-0317a8471234' )
		) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( array( 'id' => '6abc82ca-4a3a-4391-98ba-0317a8471234' ), $data );
		$this->assertSame( '6abc82ca-4a3a-4391-98ba-0317a8471234', Options::get( OptionDefaults::PIXEL_ID ) );
		$this->assertStringContainsString( '6abc82ca-4a3a-4391-98ba-0317a8471234', Transients::get( TransientDefaults::PIXEL_SCRIPT ) );
	}

	/**
	 * Test: POST /pixel returns error for unknown pixel ID.
	 */
	public function test_set_pixel_id_with_incorrect_id(): void {
		Options::set( OptionDefaults::PIXELS, $this->options['pixels_sanitized'] );

		$request  = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/pixel' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode(
			array( 'id' => 'abc123-4a3a-4391-98ba-0317a8471234' )
		) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 'pixel_not_found', $data['code'] );
		$this->assertSame( 'Selected Pixel ID does not match any known pixels.', $data['message'] );
	}

	/**
	 * Test: GET /pixel returns empty when pixel ID is not set.
	 */
	public function test_get_pixel_id_when_empty(): void {
		Options::delete( OptionDefaults::PIXEL_ID );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/pixel' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( array( 'id' => '' ), $data );
	}

	/**
	 * Test: GET /pixel returns stored pixel ID.
	 */
	public function test_get_pixel_id_when_not_empty(): void {
		Options::set( OptionDefaults::PIXEL_ID, '6abc82ca-4a3a-4391-98ba-0317a8471234' );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/pixel' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( array( 'id' => '6abc82ca-4a3a-4391-98ba-0317a8471234' ), $data );
	}

	/**
	 * Test: DELETE /pixels clears stored pixel data and cached script.
	 */
	public function test_delete_pixels(): void {
		Options::set( OptionDefaults::PIXELS, $this->options['pixels_sanitized'] );
		Options::set( OptionDefaults::PIXEL_ID, '6abc82ca-4a3a-4391-98ba-0317a8471234' );
		Transients::set( TransientDefaults::PIXEL_SCRIPT, '<script>snaptr()</script>' );

		$request  = new WP_REST_Request( 'DELETE', '/wc/sfw/snapchat/pixels' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( array( 'deleted' => true ), $data );
		$this->assertSame( Options::get( OptionDefaults::PIXELS ), array() );
		$this->assertSame( Options::get( OptionDefaults::PIXEL_ID ), '' );
		$this->assertSame( Transients::get( TransientDefaults::PIXEL_SCRIPT ), '' );
	}
}
