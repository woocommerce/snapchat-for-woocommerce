<?php
/**
 * Unit tests for the PixelTrackingService class.
 *
 * This test suite validates the behavior of PixelTrackingService in isolation.
 * It confirms that pixel tracking status is determined correctly and that
 * appropriate WordPress hooks are registered when tracking is enabled.
 * It also ensures that the service correctly delegates tracking script enqueueing.
 *
 * Note: These are pure unit tests. Integration with the actual frontend rendering
 * and script enqueueing is covered in higher-level integration tests.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Unit\Tracking;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Tracking\PixelTrackingService;
use SnapchatForWooCommerce\Tracking\PixelTrackerInterface;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Options;

/**
 * @covers \SnapchatForWooCommerce\Tracking\PixelTrackingService
 */
class PixelTrackingServiceTest extends WP_UnitTestCase {

	/**
	 * Mocked PixelTrackerInterface instance.
	 *
	 * @var PixelTrackerInterface
	 */
	private $tracker_mock;

	/**
	 * Mocked GlobalSiteTag instance.
	 *
	 * @var GlobalSiteTag
	 */
	private $global_site_tag_mock;

	/**
	 * Service under test.
	 *
	 * @var PixelTrackingService
	 */
	private $service;

	/**
	 * Sets up the test environment.
	 *
	 * Initializes the service with mock dependencies.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->tracker_mock = $this->createMock( PixelTrackerInterface::class );
		$this->service      = new PixelTrackingService( $this->tracker_mock );
	}

	/**
	 * Tears down the test environment.
	 *
	 * Cleans up any modified plugin options.
	 */
	public function tear_down(): void {
		Options::delete( OptionDefaults::PIXEL_ENABLED );

		parent::tear_down();
	}

	/**
	 * Tests that is_enabled() returns true when pixel tracking is enabled in options.
	 */
	public function test_is_enabled_returns_true_if_enabled() {
		Options::set( OptionDefaults::PIXEL_ENABLED, true );

		$this->assertTrue( PixelTrackingService::is_enabled() );
	}

	/**
	 * Tests that is_enabled() returns false when pixel tracking is disabled in options.
	 */
	public function test_is_enabled_returns_false_if_disabled() {
		Options::set( OptionDefaults::PIXEL_ENABLED, false );

		$this->assertFalse( PixelTrackingService::is_enabled() );
	}

	/**
	 * Tests that register_hooks() correctly registers hooks when pixel tracking is enabled.
	 *
	 * Also verifies that GlobalSiteTag::register() is invoked.
	 */
	public function test_register_hooks_when_enabled() {
		Options::set( OptionDefaults::PIXEL_ENABLED, true );

		$this->service->register_hooks();

		$this->assertSame( 10, has_action( 'wp_head', array( $this->tracker_mock, 'maybe_inject_pixel' ) ) );
	}

	/**
	 * Tests that register_hooks() does not register any hooks when pixel tracking is disabled.
	 *
	 * Also verifies that GlobalSiteTag::register() is not invoked.
	 */
	public function test_register_hooks_when_disabled_does_not_register() {
		Options::set( OptionDefaults::PIXEL_ENABLED, false );

		$this->service->register_hooks();

		$this->assertFalse( has_action( 'wp_head', array( $this->tracker_mock, 'maybe_inject_pixel' ) ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', array( $this->service, 'enqueue_tracking_scripts' ) ) );
	}
}
