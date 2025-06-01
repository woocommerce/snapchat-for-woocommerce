<?php

namespace SnapchatForWoocommerce\API;

use SnapchatForWoocommerce\Tracking\PixelTracker;
use SnapchatForWoocommerce\Infrastructure\WcsClient;
use SnapchatForWoocommerce\Infrastructure\JetpackAuthenticator;
use SnapchatForWoocommerce\Config\OptionDefaults;
use SnapchatForWoocommerce\Config\OptionsStore;

final class PixelTrackingService {

	private WcsClient $wcs;
	private JetpackAuthenticator $auth;
	private string $ad_account_id;

	public function __construct( WcsClient $wcs, JetpackAuthenticator $auth ) {
		$this->wcs           = $wcs;
		$this->auth          = $auth;
		$this->ad_account_id = OptionsStore::get( OptionDefaults::AD_ACCOUNT_ID );
	}

	public function register_routes(): void {
		register_rest_route( 'snapchat-ads/v1', '/settings/pixel', [
			'methods'             => [ 'GET', 'POST' ],
			'callback'            => [ $this, 'handle_toggle' ],
			'permission_callback' => '__return_true',
		] );
	}

	public function handle_toggle( \WP_REST_Request $request ) {
		if ( 'POST' === $request->get_method() ) {
			OptionsStore::set( OptionDefaults::PIXEL_ENABLED, (bool) $request->get_param( 'enabled' ) );
		}

		return rest_ensure_response([
			'enabled' => (bool) OptionsStore::get( OptionDefaults::PIXEL_ENABLED ),
		]);
	}

	public function maybe_inject_pixel(): void {
		if ( OptionsStore::get( OptionDefaults::PIXEL_ENABLED ) ) {
			$tracker = new PixelTracker( $this->wcs, $this->auth, $this->ad_account_id );
			$tracker->register_pixel_hooks();
		}
	}
}
