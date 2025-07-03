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
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
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
		Options::set( OptionDefaults::PIXEL_ENABLED, true );

		// Provide a default pixel script.
		Transients::set( TransientDefaults::PIXEL_SCRIPT, '<script src="https://sc-static.net/scevent.min.js"></script>' );

		// Provide a dummy ad account ID for API path construction.
		Options::set( OptionDefaults::ADS_ACCOUNT_ID, 'fake-account-id' );
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::PIXEL_ENABLED );
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );
		Options::delete( OptionDefaults::ADS_ACCOUNT_ID );

		parent::tear_down();
	}

	/**
	 * Test that the pixel script is rendered from cache if present.
	 */
	public function test_maybe_inject_pixel_outputs_cached_script() {
		Options::set( OptionDefaults::PIXEL_ENABLED, true );
		Transients::set( TransientDefaults::PIXEL_SCRIPT, '<script src="https://sc-static.net/scevent.min.js"></script>' );

		$wcs     = $this->createMock( WcsClient::class );
		$tracker = new RemotePixelTracker( $wcs );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script', $output );
		$this->assertStringContainsString( 'scevent.min.js', $output );
	}

	/**
	 * Test that the tracker fetches pixel script from WCS if not cached.
	 *
	 * It also asserts that the fetched script is cached for future use.
	 */
	public function test_pixel_script_fetched_from_wcs_and_cached() {
		Options::set( OptionDefaults::PIXEL_ID, 'snap-pixel-12345' );
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );

		$response_mock = $this->createMock( \WP_REST_Response::class );
		$response_mock->method( 'get_data' )->willReturn(
			array(
				'pixels' => array(
					array( 'pixel' => array( 'pixel_javascript' => '<script src="https://sc-static.net/scevent.min.js"></script>' ) ),
				),
			)
		);

		$client_mock = $this->createMock( WcsClient::class );
		$client_mock->method( 'proxy_get' )->willReturn( $response_mock );

		$tracker = new RemotePixelTracker( $client_mock );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'https://sc-static.net/scevent.min.js', $output );
	}

	/**
	 * Test that nothing is output if authentication fails.
	 */
	public function test_returns_null_if_authentication_fails() {
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );

		$client_mock = $this->createMock( WcsClient::class );

		$tracker = new RemotePixelTracker( $client_mock );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}
}
