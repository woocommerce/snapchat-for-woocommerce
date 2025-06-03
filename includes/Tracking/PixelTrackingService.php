<?php
/**
 * Service class for managing Snapchat Pixel tracking in WooCommerce.
 *
 * This class acts as the integration point between the WordPress/WooCommerce lifecycle
 * and Snapchat pixel injection logic. It registers hooks to automatically inject
 * the pixel when appropriate and provides runtime checks for whether tracking is enabled.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Utils\OptionDefaults;
use SnapchatForWooCommerce\Utils\OptionsStore;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\AssetLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the registration of pixel-related hooks and provides access to tracking status.
 *
 * This service registers frontend and REST API hooks to support pixel injection behavior.
 * Pixel rendering is delegated to a {@see PixelTracker} implementation. It also provides
 * a utility method to check whether tracking is currently enabled via plugin settings.
 *
 * Dependencies:
 * - {@see PixelTracker}: Determines if and how the pixel should be injected.
 * - {@see OptionsStore} and {@see OptionDefaults}: Used to read tracking settings.
 *
 * @since 0.1.0
 */
final class PixelTrackingService {
	/**
	 * Instance of the pixel tracker responsible for rendering the pixel.
	 *
	 * @var PixelTracker
	 */
	private PixelTracker $tracker;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PixelTracker $tracker Instance implementing the logic to inject the tracking pixel.
	 */
	public function __construct( PixelTracker $tracker ) {
		$this->tracker = $tracker;
	}

	/**
	 * Registers WordPress hooks used for pixel injection and route initialization.
	 *
	 * - Hooks into `wp_footer` to optionally output the pixel on frontend pages.
	 * - Hooks into `rest_api_init` (reserved for potential future tracking-related routes).
	 *
	 * @since 0.1.0
	 */
	public function register_hooks(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		add_action( 'wp_footer', array( $this->tracker, 'maybe_inject_pixel' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tracking_scripts' ) );
	}

	/**
	 * Determines whether Snapchat pixel tracking is currently enabled.
	 *
	 * This checks the persisted plugin option configured via the admin interface or defaults.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if pixel tracking is enabled; false otherwise.
	 */
	public static function is_enabled(): bool {
		return (bool) OptionsStore::get( OptionDefaults::PIXEL_ENABLED );
	}

	/**
	 * Enqueues script assets necessary to implement tracking.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_tracking_scripts(): void {
		AssetLoader::enqueue_script( 'pixel-tracking', 'snap-pixel.js' );
	}
}
