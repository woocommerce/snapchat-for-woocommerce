<?php
/**
 * Integration test for SnapchatAdAccountsController using real REST API call (mocked via pre_http_request).
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Admin\Settings
 */

namespace SnapchatForWooCommerce\Tests\Integration\Admin\Settings;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Tests the SnapchatAdAccountsController REST API endpoints.
 *
 * Covers GET, POST, and DELETE operations for ad account selection, including validation
 * for missing or invalid organization/ad account IDs, and cache behaviors.
 *
 * @group rest-api
 */
class SnapchatAdAccountsControllerTest extends WP_UnitTestCase {

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
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down(): void {
		parent::tear_down();
	}

	/**
	 * Extract ad accounts for a given organization ID from the cached options.
	 *
	 * @param string $org_id Organization UUID.
	 * @return array List of ad accounts.
	 */
	private function sanitize_ads( $org_id ) {
		$orgs = Options::get( OptionDefaults::ORGANIZATIONS );
		$org = current(
			array_filter(
				$orgs,
				fn( $entry ) => (string) $entry['id'] === (string) $org_id
			)
		);

		return $org['ad_accounts'];
	}

	/**
	 * Test: GET /ads_accounts returns an error when org_id is missing.
	 */
	public function test_get_ads_accounts_without_org_id() {
		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/ads_accounts' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( 'rest_missing_callback_param', $data['code'] );
		$this->assertSame( 'Missing parameter(s): org_id', $data['message'] );
	}

	/**
	 * Test: GET /ads_accounts returns error when no cached organizations exist.
	 */
	public function test_get_ads_accounts_nonexistent_organizations() {
		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/ads_accounts' );
		$request->set_query_params( array( 'org_id' => 'hello123' ) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertSame( 'no_organizations_cached', $data['code'] );
		$this->assertSame( 'Organization data not available. Please reload organizations first.', $data['message'] );
	}

	/**
	 * Test: GET /ads_accounts returns ad accounts for a valid organization ID.
	 */
	public function test_get_ads_accounts_correct_org_id() {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/ads_accounts' );
		$request->set_query_params( array( 'org_id' => '40d6719b-da09-410b-9185-0cc9c0dfed1d' ) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( $data, $this->sanitize_ads( '40d6719b-da09-410b-9185-0cc9c0dfed1d' ) );
	}

	/**
	 * Test: GET /ads_accounts returns empty when org ID is not found.
	 */
	public function test_get_ads_accounts_incorrect_org_id() {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/ads_accounts' );
		$request->set_query_params( array( 'org_id' => 'abc-da09-410b-9185-0cc9c0dfed1d' ) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertEmpty( $data );
	}

	/**
	 * Test: GET /ads_account returns empty when no ad account is set.
	 */
	public function test_get_ads_account_when_not_exists() {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/ads_account' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( array( 'id' => '' ), $data );
	}

	/**
	 * Test: GET /ads_account returns fallback value even if org is missing.
	 */
	public function test_get_ads_account_with_incorrect_id() {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'ad_account_09' );

		$request  = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/ads_account' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( array( 'id' => 'ad_account_09' ), $data );
	}

	/**
	 * Test: POST /ads_account returns error if org_id is not set.
	 */
	public function test_set_ads_account_without_org_id_set() {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );
		Options::delete( OptionDefaults::ORGANIZATION_ID );

		$request  = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/ads_account' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode(
			array( 'id' => '294da428-5f98-4c78-9946-14cf65cff14b' )
		) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( 'org_id_not_set', $data['code'] );
	}

	/**
	 * Test: POST /ads_account stores valid ad account ID.
	 */
	public function test_set_ads_account_with_correct_id() {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );
		Options::set( OptionDefaults::ORGANIZATION_ID, '40d6719b-da09-410b-9185-0cc9c0dfed1d' );

		$request  = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/ads_account' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode(
			array( 'id' => '294da428-5f98-4c78-9946-14cf65cff14b' )
		) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( array( 'id' => '294da428-5f98-4c78-9946-14cf65cff14b' ), $data );
		$this->assertSame( '294da428-5f98-4c78-9946-14cf65cff14b', Options::get( OptionDefaults::AD_ACCOUNT_ID ) );
	}

	/**
	 * Test: POST /ads_account returns error if ad account is not found.
	 */
	public function test_set_ads_account_with_incorrect_id() {
		Options::set( OptionDefaults::ORGANIZATIONS, $this->options['orgs'] );
		Options::set( OptionDefaults::ORGANIZATION_ID, '40d6719b-da09-410b-9185-0cc9c0dfed1d' );

		$request  = new WP_REST_Request( 'POST', '/wc/sfw/snapchat/ads_account' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode(
			array( 'id' => 'abc-5f98-4c78-9946-14cf65cff14b' )
		) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( 'ads_account_not_found', $data['code'] );
	}

	/**
	 * Test: DELETE /ads_account clears the stored ad account ID.
	 */
	public function test_delete_ads_account() {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, '294da428-5f98-4c78-9946-14cf65cff14b' );

		$request  = new WP_REST_Request( 'DELETE', '/wc/sfw/snapchat/ads_account' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertSame( array( 'deleted' => true ), $data );
		$this->assertSame( '', Options::get( OptionDefaults::AD_ACCOUNT_ID ) );
	}
}
