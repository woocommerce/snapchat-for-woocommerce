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
		$this->assertSame( 'Missing parameter(s): id, products_token, state', $data['message'] );
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
				array( 'id' => 'hello', 'products_token' => 'abc123', 'state' => $this->issue_oauth_state() )
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
		$request['state']          = $this->issue_oauth_state();

		$response = $controller->set_config( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertSame( 'existing-id', $data['catalog_id'] );
		$this->assertSame( 'existing-id', Options::get( OptionDefaults::CATALOG_ID ) );
	}

	/**
	 * Test: starting the OAuth flow stores a state for the current user and sends it in the return URL.
	 */
	public function test_initiate_oauth_adds_state_to_return_url(): void {
		Transients::delete( TransientDefaults::OAUTH_STATE );

		$requested_url = '';

		$this->jetpack_client
			->method( 'remote_request' )
			->willReturnCallback(
				function ( $args ) use ( &$requested_url ) {
					$requested_url = $args['url'];

					return array(
						'response' => array( 'code' => 200, 'message' => 'OK' ),
						'headers'  => array(),
						'body'     => wp_json_encode( array( 'oauth_url' => 'https://example.com/oauth' ) ),
					);
				}
			);

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/sfw/snapchat/connect' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'https://example.com/oauth', $response->get_data()['url'] );

		parse_str( (string) wp_parse_url( $requested_url, PHP_URL_QUERY ), $wcs_query );
		parse_str( (string) wp_parse_url( $wcs_query['return_url'], PHP_URL_QUERY ), $return_query );

		$this->assertSame( 'wc-admin', $return_query['page'] );
		$this->assertSame( '/snapchat/setup', $return_query['path'] );
		$this->assertMatchesRegularExpression( '/^[A-Za-z0-9]{32}$/', $return_query['sfw_oauth_state'] );

		$stored = Transients::get( TransientDefaults::OAUTH_STATE );

		$this->assertSame( get_current_user_id(), $stored['user_id'] );
		$this->assertSame( hash( 'sha256', $return_query['sfw_oauth_state'] ), $stored['hash'] );
	}

	/**
	 * Test: a failed configuration lookup keeps the saved connection unchanged.
	 */
	public function test_set_config_keeps_existing_options_when_lookup_fails(): void {
		Options::set( OptionDefaults::CONFIG_ID, 'previous-config-id' );
		Options::set( OptionDefaults::WCS_PRODUCTS_TOKEN, 'previous-products-token' );
		Options::set( OptionDefaults::ORGANIZATION_ID, 'previous-org-id' );
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );

		$state = $this->issue_oauth_state();

		$this->jetpack_client
			->method( 'remote_request' )
			->willReturn( array(
				'response' => array( 'code' => 500, 'message' => 'Internal Server Error' ),
				'headers'  => array(),
				'body'     => wp_json_encode( array( 'message' => 'Lookup failed' ) ),
			) );

		$request = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/config' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array( 'id' => 'new-config-id', 'products_token' => 'new-products-token', 'state' => $state )
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'error', $response->get_data()['status'] );
		$this->assertSame( 'previous-config-id', Options::get( OptionDefaults::CONFIG_ID ) );
		$this->assertSame( 'previous-products-token', Options::get( OptionDefaults::WCS_PRODUCTS_TOKEN ) );
		$this->assertSame( 'previous-org-id', Options::get( OptionDefaults::ORGANIZATION_ID ) );
		$this->assertSame( 'connected', Options::get( OptionDefaults::ONBOARDING_STATUS ) );
	}

	/**
	 * Test: a valid state saves the configuration and can't be used again.
	 */
	public function test_set_config_consumes_valid_state(): void {
		$this->jetpack_client
			->method( 'remote_request' )
			->willReturn( array(
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'headers'  => array(),
				'body'     => file_get_contents( $this->mock_fixture ),
			) );

		$state = $this->issue_oauth_state();

		$response = $this->dispatch_config( $state );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'hello', Options::get( OptionDefaults::CONFIG_ID ) );
		$this->assertSame( array(), Transients::get( TransientDefaults::OAUTH_STATE ) );

		Options::set( OptionDefaults::CONFIG_ID, 'saved-config-id' );

		$replay = $this->dispatch_config( $state );

		$this->assertSame( 403, $replay->get_status() );
		$this->assertSame( 'saved-config-id', Options::get( OptionDefaults::CONFIG_ID ) );
	}

	/**
	 * Test: an empty state is rejected before any option write or WCS request.
	 */
	public function test_set_config_rejects_empty_state(): void {
		$this->issue_oauth_state();

		$this->assert_config_rejected( '' );
	}

	/**
	 * Test: a state that doesn't match the pending one is rejected.
	 */
	public function test_set_config_rejects_wrong_state(): void {
		$this->issue_oauth_state();

		$this->assert_config_rejected( 'not-the-issued-state' );
	}

	/**
	 * Test: a state is rejected when nothing is pending, for example once it has expired.
	 */
	public function test_set_config_rejects_state_without_pending_flow(): void {
		Transients::delete( TransientDefaults::OAUTH_STATE );

		$this->assert_config_rejected( 'expired-or-never-issued' );
	}

	/**
	 * Test: a state issued to another user is rejected and used up.
	 */
	public function test_set_config_rejects_state_issued_to_another_user(): void {
		$state = $this->issue_oauth_state();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$this->assert_config_rejected( $state );
		$this->assertSame( array(), Transients::get( TransientDefaults::OAUTH_STATE ) );
	}

	/**
	 * Stores a pending OAuth state for the current user, as starting the connection does.
	 *
	 * @return string Raw state value.
	 */
	private function issue_oauth_state(): string {
		$state = wp_generate_password( 32, false );

		Transients::set(
			TransientDefaults::OAUTH_STATE,
			array(
				'user_id' => get_current_user_id(),
				'hash'    => hash( 'sha256', $state ),
			)
		);

		return $state;
	}

	/**
	 * Sends a config request with fixed identifiers and the given state.
	 *
	 * @param string $state Raw state value.
	 *
	 * @return WP_REST_Response
	 */
	private function dispatch_config( string $state ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/config' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array( 'id' => 'hello', 'products_token' => 'abc123', 'state' => $state )
			)
		);

		return $this->server->dispatch( $request );
	}

	/**
	 * Asserts that a config request with this state is refused without side effects.
	 *
	 * @param string $state Raw state value.
	 */
	private function assert_config_rejected( string $state ): void {
		Options::set( OptionDefaults::CONFIG_ID, 'saved-config-id' );
		Options::set( OptionDefaults::WCS_PRODUCTS_TOKEN, 'saved-products-token' );

		$this->jetpack_client->expects( $this->never() )->method( 'remote_request' );

		$response = $this->dispatch_config( $state );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'snapchat_for_woocommerce_invalid_oauth_state', $response->get_data()['code'] );
		$this->assertSame( 'saved-config-id', Options::get( OptionDefaults::CONFIG_ID ) );
		$this->assertSame( 'saved-products-token', Options::get( OptionDefaults::WCS_PRODUCTS_TOKEN ) );
		$this->assertSame( '', Options::get( OptionDefaults::ORGANIZATION_ID ) );
		$this->assertNotSame( 'connected', Options::get( OptionDefaults::ONBOARDING_STATUS ) );
	}
}
