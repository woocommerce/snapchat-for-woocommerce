<?php
/**
 * Registers and enqueues tracking-related frontend assets.
 *
 * This class is responsible for loading JavaScript required for
 * Snapchat Pixel and Conversion API tracking. It conditionally
 * loads assets based on plugin settings and localizes configuration
 * data to the frontend.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

use SnapchatForWooCommerce\Utils\AssetLoader;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Tracking\PixelTrackingService;
use SnapchatForWooCommerce\Tracking\ConversionTrackingService;

/**
 * Manages frontend asset loading for tracking features.
 *
 * Only enqueues assets if either pixel or conversion tracking is enabled.
 * Uses {@see AssetLoader} for script registration and localization.
 *
 * @since 0.1.0
 */
class Assets {
	/**
	 * Registers hooks used for enqueuing frontend scripts.
	 *
	 * Hooks into `wp_enqueue_scripts` to conditionally load tracking assets.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueues tracking scripts and localizes runtime configuration.
	 *
	 * Checks if either Pixel or Conversion API tracking is enabled.
	 * If so, enqueues the tracking script and passes localized data to JavaScript.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! ( PixelTrackingService::is_enabled() || ConversionTrackingService::is_enabled() ) ) {
			return;
		}

		AssetLoader::enqueue_script( 'tracking', 'tracking' );
		AssetLoader::localize_script(
			'tracking',
			'TrackingData',
			/**
			 * Filters the tracking configuration data passed to the frontend.
			 *
			 * This filter allows modification or extension of the localized tracking data
			 * used by the Ad Partner tracking script. You can use this to inject custom values,
			 * feature flags, or override defaults.
			 *
			 * Hook name is dynamically prefixed using {@see Helper::with_prefix()}.
			 *
			 * Example usage:
			 * ```
			 * add_filter( 'snapchat_for_woocommerce_filter_tracking_data', function( $data ) {
			 *     $data['custom_flag'] = true;
			 *     return $data;
			 * } );
			 * ```
			 *
			 * @since 0.1.0
			 *
			 * @param array $tracking_data {
			 *     Localized data for the frontend tracking script.
			 *
			 *     @type bool   $is_pixel_enabled      Whether pixel tracking is enabled.
			 *     @type bool   $is_conversion_enabled Whether conversion tracking is enabled.
			 *     @type string $capi_nonce            Nonce used for secure AJAX requests.
			 * }
			 *
			 * @return array Modified tracking data.
			 */
			apply_filters(
				Helper::with_prefix( 'filter_tracking_data' ),
				array(
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'is_pixel_enabled'      => PixelTrackingService::is_enabled(),
					'is_conversion_enabled' => ConversionTrackingService::is_enabled(),
					'capi_nonce'            => wp_create_nonce( 'capi_nonce' ),
				)
			)
		);
	}
}
