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
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\API\Site\Controllers\SnapchatOrganizationsController;
use SnapchatForWooCommerce\Connection;
use SnapchatForWooCommerce\Connection\WcsClient;

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
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->server  = rest_get_server();
		$this->options = require __DIR__ . '/fixtures/options.php';

		Options::delete( OptionDefaults::ORGANIZATION_ID );
		Options::delete( OptionDefaults::ORGANIZATION_NAME );

		$this->mock_fixture = __DIR__ . '/fixtures/organizations.json';
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down(): void {
		parent::tear_down();
	}

	/**
	 * Test: API returns empty when the org_id is not empty.
	 */
	public function test_get_organizations_org_id_not_empty(): void {
		Options::set( OptionDefaults::ORGANIZATION_ID, $this->options['org_id'] );
		Options::set( OptionDefaults::ORGANIZATION_NAME, $this->options['org_name'] );

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
