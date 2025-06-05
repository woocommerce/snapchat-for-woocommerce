<?php
/**
 * Integration tests for the RemotePixelTracker class.
 *
 * These tests validate that pixel injection behaves correctly based on plugin settings,
 * including caching, fallbacks, and integration with the WCS proxy layer.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Integration\Tracking;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Utils\OptionsStore;
use SnapchatForWooCommerce\Utils\OptionDefaults;
use SnapchatForWooCommerce\Tracking\RemotePixelTracker;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Connection\WcsClient;

/**
 * @covers \SnapchatForWooCommerce\Tracking\RemotePixelTracker
 */
class RemotePixelTrackerTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		// Enable pixel tracking.
		OptionsStore::set( OptionDefaults::PIXEL_ENABLED, true );

		// Provide a default pixel script.
		OptionsStore::set( OptionDefaults::PIXEL_SCRIPT, '<script src="snap.js"></script>' );

		// Provide a dummy ad account ID for API path construction.
		OptionsStore::set( OptionDefaults::AD_ACCOUNT_ID, 'fake-account-id' );
	}


	/**
	 * Cleanup plugin options after each test.
	 */
	public function tear_down(): void {
		OptionsStore::delete( OptionDefaults::PIXEL_ENABLED );
		OptionsStore::delete( OptionDefaults::PIXEL_SCRIPT );
		OptionsStore::delete( OptionDefaults::AD_ACCOUNT_ID );

		parent::tear_down();
	}

	/**
	 * Test that the pixel script is rendered from cache if present.
	 */
	public function test_maybe_inject_pixel_outputs_cached_script() {
		OptionsStore::set( OptionDefaults::PIXEL_ENABLED, true );
		OptionsStore::set( OptionDefaults::PIXEL_SCRIPT, '<script src="snap.js"></script>' );

		$auth    = $this->createMock( JetpackAuthenticator::class );
		$wcs     = $this->createMock( WcsClient::class );
		$tracker = new RemotePixelTracker( $wcs, $auth );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script', $output );
		$this->assertStringContainsString( 'snap.js', $output );
	}

	/**
	 * Test that the tracker fetches pixel script from WCS if not cached.
	 *
	 * It also asserts that the fetched script is cached for future use.
	 */
	public function test_pixel_script_fetched_from_wcs_and_cached() {
		OptionsStore::delete( OptionDefaults::PIXEL_SCRIPT );

		$auth_mock = $this->createMock( JetpackAuthenticator::class );
		$auth_mock->method( 'get_auth_header' )->willReturn( 'Bearer token' );

		$response_mock = $this->createMock( \WP_REST_Response::class );
		$response_mock->method( 'get_data' )->willReturn(
			array(
				'pixels' => array(
					array( 'pixel' => array( 'pixel_javascript' => '<script>remote pixel</script>' ) ),
				),
			)
		);

		$client_mock = $this->createMock( WcsClient::class );
		$client_mock->method( 'proxy_get' )->willReturn( $response_mock );

		$tracker = new RemotePixelTracker( $client_mock, $auth_mock );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'remote pixel', $output );
	}

	/**
	 * Test that nothing is output if authentication fails.
	 */
	public function test_returns_null_if_authentication_fails() {
		OptionsStore::delete( OptionDefaults::PIXEL_SCRIPT );

		$auth_mock = $this->createMock( JetpackAuthenticator::class );
		$auth_mock->method( 'get_auth_header' )->willReturn( new \WP_Error( 'auth_fail', 'failed' ) );

		$client_mock = $this->createMock( WcsClient::class );

		$tracker = new RemotePixelTracker( $client_mock, $auth_mock );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}
}
