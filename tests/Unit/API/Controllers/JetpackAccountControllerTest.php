<?php
/**
 * Unit tests for the Jetpack account REST controller.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\API\Controllers
 */

namespace SnapchatForWooCommerce\Tests\Unit\API\Controllers;

use Automattic\Jetpack\Connection\Manager;
use SnapchatForWooCommerce\API\Site\Controllers\JetpackAccountController;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Plugin;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Options;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * @covers \SnapchatForWooCommerce\API\Site\Controllers\JetpackAccountController
 */
class JetpackAccountControllerTest extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Mocked Jetpack connection manager.
	 *
	 * @var Manager|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $manager;

	/**
	 * Registers the controller routes with a mocked connection manager.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->manager = $this->createMock( Manager::class );

		remove_action( 'rest_api_init', array( Plugin::class, 'register_rest_routes' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		$GLOBALS['wp_rest_server'] = null;
		do_action( 'rest_api_init' );
		$this->server = rest_get_server();
	}

	/**
	 * Restores the plugin's own route registration.
	 */
	public function tear_down(): void {
		remove_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'rest_api_init', array( Plugin::class, 'register_rest_routes' ) );
		$GLOBALS['wp_rest_server'] = null;

		Options::delete( OptionDefaults::IS_JETPACK_CONNECTED );
		Options::delete( OptionDefaults::WP_TOS_ACCEPTED );

		parent::tear_down();
	}

	/**
	 * Registers the controller under test.
	 */
	public function register_routes(): void {
		( new JetpackAccountController( $this->createMock( WcsClient::class ), $this->manager ) )->register_routes();
	}

	/**
	 * Ensures the connect route only accepts GET requests.
	 */
	public function test_connect_route_only_supports_get(): void {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/sfw/jetpack/connect', $routes );

		$methods = array();
		foreach ( $routes['/wc/sfw/jetpack/connect'] as $handler ) {
			$methods = array_merge( $methods, array_keys( $handler['methods'] ) );
		}

		$this->assertSame( array( 'GET' ), array_values( array_unique( $methods ) ) );
	}

	/**
	 * Ensures a DELETE request is not found and changes nothing, for any role.
	 *
	 * @dataProvider role_provider
	 *
	 * @param string $role User role.
	 */
	public function test_delete_request_is_not_found_and_changes_nothing( string $role ): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => $role ) ) );

		Options::set( OptionDefaults::IS_JETPACK_CONNECTED, 'yes' );
		Options::set( OptionDefaults::WP_TOS_ACCEPTED, 'yes' );

		$this->manager->expects( $this->never() )->method( 'remove_connection' );

		$response = $this->server->dispatch( new WP_REST_Request( 'DELETE', '/wc/sfw/jetpack/connect' ) );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'rest_no_route', $response->get_data()['code'] );
		$this->assertSame( 'yes', Options::get( OptionDefaults::IS_JETPACK_CONNECTED ) );
		$this->assertSame( 'yes', Options::get( OptionDefaults::WP_TOS_ACCEPTED ) );
	}

	/**
	 * Provides the roles that could call the removed route.
	 *
	 * @return array<string,array<string>>
	 */
	public function role_provider(): array {
		return array(
			'administrator' => array( 'administrator' ),
			'shop manager'  => array( 'shop_manager' ),
		);
	}
}
