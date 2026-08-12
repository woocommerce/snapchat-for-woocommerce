<?php
/**
 * Unit tests for sandbox mode.
 *
 * @package SnapchatForWooCommerce\Tests\Unit
 */

namespace SnapchatForWooCommerce\Tests\Unit;

use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Connection\JetpackClient;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\SandboxMode;
use SnapchatForWooCommerce\Tracking\ConversionTrackingService;
use SnapchatForWooCommerce\Tracking\PixelTrackingService;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * @covers \SnapchatForWooCommerce\SandboxMode
 */
class SandboxModeTest extends WP_UnitTestCase {
	/**
	 * Sandbox service under test.
	 *
	 * @var SandboxMode
	 */
	private $sandbox;

	/**
	 * Sets up an isolated option state.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->sandbox = new SandboxMode();
		delete_option( SandboxMode::OPTION_NAME );
		delete_option( SandboxMode::SETTINGS_OPTION_NAME );
	}

	/**
	 * Removes options changed by these tests.
	 */
	public function tear_down(): void {
		delete_option( SandboxMode::OPTION_NAME );
		delete_option( SandboxMode::SETTINGS_OPTION_NAME );
		delete_option( Options::get_key( OptionDefaults::ONBOARDING_STATUS ) );
		delete_option( Options::get_key( OptionDefaults::CONVERSIONS_ENABLED ) );
		delete_option( Options::get_key( OptionDefaults::PIXEL_ENABLED ) );

		parent::tear_down();
	}

	/**
	 * Verifies accepted database option values.
	 *
	 * @dataProvider enabled_values_provider
	 *
	 * @param mixed $value Option value.
	 */
	public function test_database_option_enables_sandbox_mode( $value ): void {
		update_option( SandboxMode::OPTION_NAME, $value );

		$this->assertTrue( SandboxMode::is_enabled() );
	}

	/**
	 * Provides accepted option values.
	 *
	 * @return array<string,array<mixed>>
	 */
	public function enabled_values_provider(): array {
		return array(
			'yes'     => array( 'yes' ),
			'true'    => array( true ),
			'integer' => array( 1 ),
			'on'      => array( 'on' ),
		);
	}

	/**
	 * Verifies sandbox mode is disabled by default.
	 */
	public function test_sandbox_mode_is_disabled_by_default(): void {
		$this->assertFalse( SandboxMode::is_enabled() );
	}

	/**
	 * Ensures the connected state is projected without changing live onboarding.
	 */
	public function test_onboarding_state_is_virtual(): void {
		update_option( Options::get_key( OptionDefaults::ONBOARDING_STATUS ), 'incomplete' );
		update_option( SandboxMode::OPTION_NAME, 'yes' );

		$this->assertSame( 'connected', Options::get( OptionDefaults::ONBOARDING_STATUS ) );
		update_option( SandboxMode::OPTION_NAME, 'no' );
		$this->assertSame( 'incomplete', get_option( Options::get_key( OptionDefaults::ONBOARDING_STATUS ) ) );
	}

	/**
	 * Ensures sandbox setting writes do not alter their live counterparts.
	 */
	public function test_settings_are_stored_separately(): void {
		update_option( Options::get_key( OptionDefaults::CONVERSIONS_ENABLED ), 'no' );
		update_option( SandboxMode::OPTION_NAME, 'yes' );

		$this->sandbox->save_conversion_setting( 'yes', 'no' );

		update_option( SandboxMode::OPTION_NAME, 'no' );
		$this->assertSame( 'no', get_option( Options::get_key( OptionDefaults::CONVERSIONS_ENABLED ) ) );
		$this->assertSame(
			array( 'conversion_enabled' => 'yes' ),
			get_option( SandboxMode::SETTINGS_OPTION_NAME )
		);
	}

	/**
	 * Ensures account endpoints return simulated data.
	 */
	public function test_account_rest_response_is_simulated(): void {
		update_option( SandboxMode::OPTION_NAME, 'yes' );
		$this->sandbox->register_hooks();
		$request = new WP_REST_Request( 'GET', '/wc/sfw/snapchat/account' );
		$response = apply_filters( 'rest_dispatch_request', null, $request, '', array() );
		$this->sandbox->unregister_hooks();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'Sandbox Organization', $response->get_data()['org_name'] );
		$this->assertSame( 'sandbox-ad-account', $response->get_data()['ad_acc_id'] );
	}

	/**
	 * Ensures account mutations are rejected server-side.
	 */
	public function test_disconnect_rest_request_is_blocked(): void {
		update_option( SandboxMode::OPTION_NAME, 'yes' );
		$request  = new WP_REST_Request( 'DELETE', '/wc/sfw/snapchat/connection' );
		$response = $this->sandbox->intercept_rest_request( null, $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'snapchat_sandbox_action_disabled', $response->get_error_code() );
	}

	/**
	 * Ensures both storefront tracking services are disabled.
	 */
	public function test_tracking_is_disabled(): void {
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );
		update_option( SandboxMode::OPTION_NAME, 'yes' );

		$this->assertFalse( PixelTrackingService::is_enabled() );
		$this->assertFalse( ConversionTrackingService::is_enabled() );
	}

	/**
	 * Ensures the WCS client cannot make a remote request in sandbox mode.
	 */
	public function test_wcs_requests_are_blocked(): void {
		update_option( SandboxMode::OPTION_NAME, 'yes' );

		$authenticator = $this->createMock( JetpackAuthenticator::class );
		$jetpack_client = $this->createMock( JetpackClient::class );
		$jetpack_client->expects( $this->never() )->method( 'remote_request' );
		$client = new WcsClient( $authenticator, $jetpack_client );

		$response = $client->proxy_get( 'connection/status' );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'snapchat_sandbox_remote_request_blocked', $response->get_error_code() );
	}
}
