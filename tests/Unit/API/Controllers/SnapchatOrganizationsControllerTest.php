<?php
/**
 * Integration test for SnapchatOrganizationsController using real REST API call (mocked via pre_http_request).
 *
 * @package SnapchatForWooCommerce\Tests\Integration\API\Controllers
 */

namespace SnapchatForWooCommerce\Tests\Integration\API\Controllers;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Tests the SnapchatOrganizationsController REST API endpoints.
 *
 * @group rest-api
 */
class SnapchatOrganizationsControllerTest extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Path to mock organization fixture file.
	 *
	 * @var string
	 */
	protected $mock_fixture;

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

		Options::delete( OptionDefaults::ORGANIZATION_ID );
		Options::delete( OptionDefaults::ORGANIZATION_NAME );

		add_filter( Helper::with_prefix( 'jetpack_auth_token' ), fn() => 'abc123' );

		$this->mock_fixture = __DIR__ . '/fixtures/organizations.json';
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down(): void {
		parent::tear_down();
	}

	/**
	 * Intercept HTTP requests and return an empty JSON body.
	 *
	 * @param mixed  $preempt     Preemptive response.
	 * @param array  $parsed_args Parsed request arguments.
	 * @param string $url         Request URL.
	 * @return array|mixed
	 */
	public function intercept_wcs_http_requests_with_empty_response( $preempt, $parsed_args, $url ) {
		if ( str_contains( $url, '/ads/v1/organizations' ) !== false ) {
			return array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => '{}',
			);
		}

		return $preempt;
	}

	/**
	 * Intercept HTTP requests and return fixture data.
	 *
	 * @param mixed  $preempt     Preemptive response.
	 * @param array  $parsed_args Parsed request arguments.
	 * @param string $url         Request URL.
	 * @return array|mixed
	 */
	public function intercept_wcs_http_requests_with_non_empty_response( $preempt, $parsed_args, $url ) {
		if ( str_contains( $url, '/ads/v1/organizations' ) !== false ) {
			return array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => file_get_contents( $this->mock_fixture ),
			);
		}

		return $preempt;
	}

	/**
	 * Test: API returns empty when the org_id is empty.
	 */
	public function test_get_organizations_org_id_empty(): void {
		add_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_empty_response' ), 10, 3 );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organization' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_empty_response' ), 10 );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertSame( array( 'id' => '', 'name' => '' ), $data );
	}

	/**
	 * Test: API returns empty when the org_id is not empty.
	 */
	public function test_get_organizations_org_id_not_empty(): void {
		Options::set( OptionDefaults::ORGANIZATION_ID, $this->options['org_id'] );
		add_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_non_empty_response' ), 10, 3 );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organization' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_non_empty_response' ), 10 );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertSame( array( 'id' => $this->options['org_id'], 'name' => $this->options['org_name'] ), $data );
	}
}
