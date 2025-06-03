<?php

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Utils\OptionDefaults;
use SnapchatForWooCommerce\Utils\OptionsStore;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

final class PixelTrackingService {

	private PixelTracker $tracker;

	public function __construct( PixelTracker $tracker ) {
		$this->tracker = $tracker;
	}

	/**
	 * Hook into WordPress lifecycle.
	 */
	public function register_hooks(): void {
		add_action( 'wp_head', [ $this->tracker, 'maybe_inject_pixel' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes for toggling pixel.
	 */
	public function register_routes(): void {
		register_rest_route( 'snapchat-ads/v1', '/tracking/pixel', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'update_pixel_settings' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_woocommerce' );
			},
		] );
	}

	/**
	 * Store pixel tracking setting.
	 */
	public function update_pixel_settings( WP_REST_Request $request ) {
		$enabled = (bool) $request->get_param( 'enabled' );

		OptionsStore::set( OptionDefaults::PIXEL_ENABLED, $enabled );

		return new WP_REST_Response( [
			'enabled' => $enabled,
		] );
	}

	/**
	 * Get current pixel tracking state.
	 */
	public function is_enabled(): bool {
		return (bool) OptionsStore::get( OptionDefaults::PIXEL_ENABLED );
	}
}
