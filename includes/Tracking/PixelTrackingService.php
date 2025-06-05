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
use SnapchatForWooCommerce\Utils\AssetLoader;

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
	 * Instance responsible for injecting the Ad Partner Global Site Tag into the site header.
	 *
	 * This service ensures the tracking events are added to the frontend when tracking
	 * is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @var GlobalSiteTag
	 */
	private GlobalSiteTag $global_site_tag;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PixelTracker  $tracker          Instance implementing the logic to inject the tracking pixel.
	 * @param GlobalSiteTag $global_site_tag  Instance responsible for injecting the Ad Partner Global Site Tag.
	 */
	public function __construct( PixelTracker $tracker, GlobalSiteTag $global_site_tag ) {
		$this->tracker         = $tracker;
		$this->global_site_tag = $global_site_tag;
	}

	/**
	 * Registers WordPress hooks used for pixel injection and route initialization.
	 *
	 * - Hooks into `wp_head` to optionally output the pixel on frontend pages.
	 * - Registers global site tag logic.
	 * - Enqueues external tracking assets.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		add_action( 'wp_head', array( $this->tracker, 'maybe_inject_pixel' ) );

		$this->global_site_tag->register();

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
		AssetLoader::enqueue_script( 'pixel-tracking', 'snap-pixel' );
	}
}
