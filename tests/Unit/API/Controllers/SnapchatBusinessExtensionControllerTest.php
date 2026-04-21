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
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\API\Site\Controllers\SnapchatBusinessExtensionController;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Connection\JetpackClient;
use SnapchatForWooCommerce\Plugin;
use SnapchatForWooCommerce\API\AdPartner\AdPartnerApi;
use SnapchatForWooCommerce\API\AdPartner\CatalogApi;

/**
 * Tests the SnapchatOrganizationsController REST API endpoints.
 */
class SnapchatBusinessExtensionControllerTest extends WP_UnitTestCase {

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

		$this->options = require __DIR__ . '/fixtures/options.php';

		$user_id = $this->factory->user->create(
			array( 'role' => 'administrator' )
		);
		wp_set_current_user( $user_id );

		Options::delete( OptionDefaults::ORGANIZATION_ID );
		Options::delete( OptionDefaults::ORGANIZATION_NAME );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		Options::delete( OptionDefaults::PIXEL_ID );
		Options::delete( OptionDefaults::CATALOG_ID );
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );
		Options::delete( OptionDefaults::ONBOARDING_STATUS );

		add_filter( Helper::with_prefix( 'jetpack_auth_token' ), fn() => 'abc123' );

		$this->mock_fixture = __DIR__ . '/fixtures/snapchat-config.json';
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down(): void {
		remove_action( 'rest_api_init', array( $this, 'register_route' ) );
		add_action( 'rest_api_init', array( Plugin::class, 'register_rest_routes' ) );
		do_action( 'rest_api_init' );
		parent::tear_down();
	}

	/**
	 * Register the REST route with mocked Jetpack client.
	 */
	public function register_route(): void {
		$wcs = new WcsClient(
			new JetpackAuthenticator(),
			$this->jetpack_client
		);
		$mock_ad_partner_api = $this->createMock( AdPartnerApi::class );
		$mock_catalog        = $this->createMock( CatalogApi::class );

		// `find_or_create()` returns a response shaped like `create()` so
		// existing assertions keep working after the SNAPWOO-75 fix.
		$mock_catalog->method( 'find_or_create' )->willReturn(
			new \WP_REST_Response(
				array(
					'catalogs' => array(
						array(
							'catalog' => array(
								'id' => 'mock-catalog-id',
							),
						),
					),
				)
			)
		);

		$mock_ad_partner_api->catalog = $mock_catalog;

		$controller = new SnapchatBusinessExtensionController( $wcs, $mock_ad_partner_api );
		$controller->register_routes();
	}

	/**
	 * Test: API returns errors without config id.
	 */
	public function test_get_config_without_config_id_and_products_token(): void {
		$this->jetpack_client
			->method( 'remote_request' )
			->willReturn( array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => file_get_contents( $this->mock_fixture ),
			) );

		$request  = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/config' );
		$response = $this->server->dispatch( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertSame( 'rest_missing_callback_param', $data['code'] );
		$this->assertSame( 'Missing parameter(s): id, products_token', $data['message'] );
	}

	/**
	 * Test: API sets options with correct config id.
	 */
	public function test_get_config_with_config_id(): void {
		$this->jetpack_client
			->method( 'remote_request' )
			->willReturn( array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => file_get_contents( $this->mock_fixture ),
			) );

		$request = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/config' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array( 'id' => 'hello', 'products_token' => 'abc123' )
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertSame( array(
			'org_id'      => '0877b15f-518h-4e8d-93a7-2e83b8329a00',
			'org_name'    => 'Seireitei',
			'ad_acc_id'   => 'be1l1a65-e320-456f-4a49-68999aee29c5',
			'ad_acc_name' => 'Squad 6',
			'pixel_id'    => 'a6458d50-44a3-42e2-65e4-ed1943j59da4',
			'catalog_id'  => 'mock-catalog-id',
		), $data );
		$this->assertSame( $this->options['org_id'], Options::get( OptionDefaults::ORGANIZATION_ID ) );
		$this->assertSame( 'Seireitei', Options::get( OptionDefaults::ORGANIZATION_NAME ) );
		$this->assertSame( $this->options['ad_account_id'], Options::get( OptionDefaults::AD_ACCOUNT_ID ) );
		$this->assertSame( $this->options['pixel_id'], Options::get( OptionDefaults::PIXEL_ID ) );
		$this->assertSame( '', Transients::get( TransientDefaults::PIXEL_SCRIPT ) );
		$this->assertSame( 'connected', Options::get( OptionDefaults::ONBOARDING_STATUS ) );
	}

	/**
	 * Test: when a CATALOG_ID is already stored (reconnect scenario), the controller
	 * reuses it via `find_or_create()` and does NOT call `create()`.
	 *
	 * Verifies the fix for SNAPWOO-75: disconnecting and reconnecting must not
	 * create a duplicate remote catalog on Snapchat's side.
	 *
	 * Invokes `set_config()` directly rather than round-tripping through the REST
	 * server so the `never()` expectation can attach to the specific CatalogApi
	 * mock wired into the controller under test.
	 */
	public function test_set_config_reuses_existing_catalog_id(): void {
		Options::set( OptionDefaults::CATALOG_ID, 'existing-id' );

		$this->jetpack_client
			->method( 'remote_request' )
			->willReturn( array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => file_get_contents( $this->mock_fixture ),
			) );

		$mock_catalog = $this->createMock( CatalogApi::class );
		$mock_catalog->method( 'find_or_create' )->willReturn(
			new \WP_REST_Response(
				array(
					'catalogs' => array(
						array(
							'catalog' => array( 'id' => 'existing-id' ),
						),
					),
				)
			)
		);
		$mock_catalog->expects( $this->never() )->method( 'create' );

		$wcs = new WcsClient(
			new JetpackAuthenticator(),
			$this->jetpack_client
		);
		$mock_ad_partner_api          = $this->createMock( AdPartnerApi::class );
		$mock_ad_partner_api->catalog = $mock_catalog;

		$controller = new SnapchatBusinessExtensionController( $wcs, $mock_ad_partner_api );

		$request = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/config' );
		$request['id']             = 'hello';
		$request['products_token'] = 'abc123';

		$response = $controller->set_config( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertSame( 'existing-id', $data['catalog_id'] );
		$this->assertSame( 'existing-id', Options::get( OptionDefaults::CATALOG_ID ) );
	}
}
