<?php
/**
 * REST API service for managing Ad Partner pixel tracking.
 *
 * Registers API endpoints to toggle tracking pixel settings and conditionally
 * injects the Ad Partner pixel into WooCommerce-enabled frontend pages.
 *
 * @package SnapchatForWoocommerce\API
 */

namespace SnapchatForWoocommerce\API;

use SnapchatForWoocommerce\Tracking\PixelTracker;
use SnapchatForWoocommerce\Infrastructure\WcsClient;
use SnapchatForWoocommerce\Infrastructure\JetpackAuthenticator;
use SnapchatForWoocommerce\Config\OptionDefaults;
use SnapchatForWoocommerce\Config\OptionsStore;

/**
 * Service responsible for Ad Partner pixel tracking logic.
 *
 * Responsibilities:
 * - Expose a REST route to enable or disable pixel tracking.
 * - Persist toggle state via WordPress options.
 * - Inject pixel if enabled using PixelTracker hooks.
 *
 * ## Routes:
 * - `GET  /settings/pixel` — Returns whether tracking is enabled.
 * - `POST /settings/pixel` — Updates enablement state.
 *
 * ## Dependencies:
 * - {@see WcsClient} – Builds URLs for communicating with Ad Partner's remote API.
 * - {@see JetpackAuthenticator} – Supplies authenticated headers via `X_JP_Auth`.
 * - {@see OptionsStore} – Stores and retrieves pixel state and Ad Account ID.
 * - {@see OptionDefaults} – Provides constants for option keys.
 * - {@see PixelTracker} – Registers hooks to output pixel in frontend.
 */
final class PixelTrackingService {

	/**
	 * WooCommerce Services client for API interaction.
	 *
	 * @var WcsClient
	 */
	private WcsClient $wcs;

	/**
	 * Authenticator providing secure headers for proxy communication.
	 *
	 * @var JetpackAuthenticator
	 */
	private JetpackAuthenticator $auth;

	/**
	 * Ad Partner ad account identifier, required for pixel registration.
	 *
	 * @var string
	 */
	private string $ad_account_id;

	/**
	 * Constructor.
	 *
	 * Initializes dependencies and loads persisted Ad Account ID.
	 *
	 * @param WcsClient $wcs  WooCommerce Services API client.
	 * @param JetpackAuthenticator $auth Auth provider for secure headers.
	 */
	public function __construct( WcsClient $wcs, JetpackAuthenticator $auth ) {
		$this->wcs           = $wcs;
		$this->auth          = $auth;
		$this->ad_account_id = OptionsStore::get( OptionDefaults::AD_ACCOUNT_ID );
	}

	/**
	 * Registers the REST API route for pixel tracking settings.
	 *
	 * This route responds to:
	 * - GET  → to return whether the pixel is enabled.
	 * - POST → to update pixel enabled state via payload.
	 *
	 * @return void
	 */
	public function register_routes(): void {}

	/**
	 * Conditionally injects the Ad Partner pixel based on saved settings.
	 *
	 * Instantiates a PixelTracker if pixel tracking is enabled and registers
	 * appropriate WooCommerce/WordPress hooks.
	 *
	 * @return void
	 */
	public function maybe_inject_pixel(): void {
		if ( OptionsStore::get( OptionDefaults::PIXEL_ENABLED ) ) {
			$tracker = new PixelTracker( $this->wcs, $this->auth, $this->ad_account_id );
			$tracker->register_pixel_hooks();
		}
	}
}
