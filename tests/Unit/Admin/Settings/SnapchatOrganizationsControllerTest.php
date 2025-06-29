<?php
/**
 * Integration test for SnapchatOrganizationsController using real REST API call (mocked via pre_http_request).
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

		Options::delete( OptionDefaults::ORGANIZATIONS );
		Options::delete( OptionDefaults::ORGANIZATION_ID );

		add_filter( Helper::with_prefix( 'jetpack_auth_token' ), fn() => 'abc123' );

		$this->mock_fixture = __DIR__ . '/fixtures/organizations-with-ad-accounts.json';
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
		if ( strpos( $url, '/ads/v1/me/organizations?with_ad_accounts=true' ) !== false ) {
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
		if ( strpos( $url, '/ads/v1/me/organizations?with_ad_accounts=true' ) !== false ) {
			return array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => file_get_contents( $this->mock_fixture ),
			);
		}

		return $preempt;
	}

	/**
	 * Test: API returns empty when the response has no organizations.
	 */
	public function test_get_organizations_when_token_is_invalid(): void {
		add_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_empty_response' ), 10, 3 );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organizations' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_empty_response' ), 10 );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertCount( 0, $data );
	}

	/**
	 * Test: API returns empty array when response body is empty JSON.
	 */
	public function test_get_organizations_when_response_is_empty(): void {
		add_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_empty_response' ), 10, 3 );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organizations' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_empty_response' ), 10 );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertCount( 0, $data );
	}

	/**
	 * Test: API fetches and caches data when no cache exists.
	 */
	public function test_get_organizations_fetches_from_wcs_when_cache_empty(): void {
		add_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_non_empty_response' ), 10, 3 );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organizations' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'intercept_wcs_http_requests_with_non_empty_response' ), 10 );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertCount( 3, $data );
		$this->assertEquals( 'Hooli Inc', $data[0]['name'] );
		$this->assertEquals( 'Pied Piper LLC', $data[1]['name'] );
		$this->assertEquals( 'Initech Corp', $data[2]['name'] );

		$cached = Options::get( OptionDefaults::ORGANIZATIONS );
		$this->assertIsArray( $cached );
		$this->assertCount( 3, $cached );

		$this->assertEquals( $cached, $this->options['orgs'] );
	}

	/**
	 * Test: API uses cached data if it already exists.
	 */
	public function test_get_organizations_fetches_from_wcs_when_cache_not_empty(): void {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organizations' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( $data, $this->options['orgs_sanitized'] );
	}

	/**
	 * Test: Deleting cached organizations clears the option.
	 */
	public function test_delete_organizations(): void {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );

		$request  = new WP_REST_Request( 'DELETE', '/wc/sfw/snapchat/organizations' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( $data, array( 'deleted' => true ) );
		$this->assertSame( Options::get( OptionDefaults::ORGANIZATIONS ), array() );
	}

	/**
	 * Test: Getting selected organization returns default if none set.
	 */
	public function test_get_organization_when_does_not_exist(): void {
		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organization' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertEquals( array( 'id' => '' ), $data );
	}

	/**
	 * Test: Selected organization ID not found in list returns empty.
	 */
	public function test_get_organization_with_non_existent_id(): void {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );
		Options::set( OptionDefaults::ORGANIZATION_ID, 'hello' );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organization' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertEquals( array( 'id' => '' ), $data );
	}

	/**
	 * Test: Correct organization ID returns expected value.
	 */
	public function test_get_organization_with_correct_id(): void {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );
		Options::set( OptionDefaults::ORGANIZATION_ID, '40d6719b-da09-410b-9185-0cc9c0dfed1d' );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organization' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertEquals(
			array( 'id' => '40d6719b-da09-410b-9185-0cc9c0dfed1d' ),
			$data
		);
	}

	/**
	 * Test: Setting a non-existent organization ID returns empty.
	 */
	public function test_set_organization_with_non_existent_id(): void {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );

		$request = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/organization' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array( 'id' => 'hello' )
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( array( 'id' => '' ), $data );
	}

	/**
	 * Test: Successfully set and return a valid organization ID.
	 */
	public function test_set_organization_with_existent_id(): void {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );

		$request = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/organization' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array( 'id' => 'b35f4c9f-a123-4cde-8934-d32b4b91a731' )
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals(
			array( 'id' => 'b35f4c9f-a123-4cde-8934-d32b4b91a731' ),
			$data
		);

		// Now test again after removing org list
		Options::delete( OptionDefaults::ORGANIZATIONS );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( array( 'id' => '' ), $data );
	}
}
