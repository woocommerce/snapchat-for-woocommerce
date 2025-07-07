<?php
/**
 * Integration test for SnapchatOrganizationsController using mocked JetpackClient.
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
use SnapchatForWooCommerce\API\Site\Controllers\SnapchatOrganizationsController;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Connection\JetpackClient;
use SnapchatForWooCommerce\Plugin;

/**
 * Tests the SnapchatOrganizationsController REST API endpoints.
 */
class SnapchatOrganizationsControllerTest extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected WP_REST_Server $server;

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
	 * Mocked Jetpack client.
	 *
	 * @var JetpackClient
	 */
	private $jetpack_client;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->jetpack_client = $this->createMock( JetpackClient::class );

		remove_action( 'rest_api_init', array( Plugin::class, 'register_rest_routes' ) );
		add_action( 'rest_api_init', array( $this, 'register_route' ) );

		$GLOBALS['wp_rest_server'] = null;
		do_action( 'rest_api_init' );
		$this->server = rest_get_server();

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

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
		remove_action( 'rest_api_init', array( $this, 'register_route' ) );
		add_action( 'rest_api_init', array( Plugin::class, 'register_rest_routes' ) );
		parent::tear_down();
	}

	/**
	 * Register the REST route with mocked Jetpack client.
	 */
	public function register_route(): void {
		$controller = new SnapchatOrganizationsController(
			new WcsClient(
				new JetpackAuthenticator(),
				$this->jetpack_client
			)
		);
		$controller->register_routes();
	}

	/**
	 * Test: API returns empty when the org_id is empty.
	 */
	public function test_get_organizations_org_id_empty(): void {
		$this->jetpack_client
			->method( 'remote_request' )
			->willReturn( array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => '{}',
			) );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organization' );
		$response = $this->server->dispatch( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame(
			array( 'id' => '', 'name' => '' ),
			$response->get_data()
		);
	}

	/**
	 * Test: API returns empty when the org_id is not empty.
	 */
	public function test_get_organizations_org_id_not_empty(): void {
		Options::set( OptionDefaults::ORGANIZATION_ID, $this->options['org_id'] );

		$this->jetpack_client
			->method( 'remote_request' )
			->willReturn( array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => file_get_contents( $this->mock_fixture ),
			) );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/organization' );
		$response = $this->server->dispatch( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame(
			array(
				'id'   => $this->options['org_id'],
				'name' => $this->options['org_name'],
			),
			$response->get_data()
		);
	}
}
