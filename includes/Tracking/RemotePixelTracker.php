<?php
/**
 * Implementation of PixelTracker that retrieves and injects the Snapchat Pixel script remotely.
 *
 * This tracker checks whether pixel tracking is enabled, and if so, either retrieves a cached script
 * or fetches a fresh one from Snapchat via the WCS API. The script is optionally personalized
 * with the logged-in user’s email address for audience matching.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * Fetches and injects Snapchat pixel tracking code into WooCommerce frontend pages.
 *
 * Responsibilities:
 * - Checks plugin option to determine if pixel tracking is enabled.
 * - Retrieves pixel script from local cache or Snapchat Ads API.
 * - Injects sanitized pixel JavaScript into the frontend via `wp_head`.
 * - Optionally personalizes the script using the logged-in user's email.
 *
 * Dependencies:
 * - {@see WcsClient} for making remote API calls to fetch the pixel script.
 * - {@see JetpackAuthenticator} for securely authenticating requests.
 * - {@see OptionsStore} and {@see OptionDefaults} for managing plugin settings and cache.
 *
 * @see \SnapchatForWooCommerce\Tracking\PixelTracker
 * @since 0.1.0
 */
final class RemotePixelTracker implements PixelTracker {

	/**
	 * Client for making authenticated proxy requests to Snapchat Ads API.
	 *
	 * @var WcsClient
	 */
	private WcsClient $wcs_client;

	/**
	 * Authenticator to generate secure headers for Snapchat API requests.
	 *
	 * @var JetpackAuthenticator
	 */
	private JetpackAuthenticator $auth;

	/**
	 * Constructor.
	 *
	 * @param WcsClient            $wcs_client WCS API client.
	 * @param JetpackAuthenticator $auth       Authenticator for API access.
	 */
	public function __construct( WcsClient $wcs_client, JetpackAuthenticator $auth ) {
		$this->wcs_client = $wcs_client;
		$this->auth       = $auth;
	}

	/**
	 * Injects the Snapchat Pixel script into the footer.
	 * Personalized if possible, and sanitized using `wp_kses`.
	 *
	 * @since 0.1.0
	 */
	public function maybe_inject_pixel(): void {
		$allowed_tags = array(
			'script'   => array(
				'type'  => array(),
				'src'   => array(),
				'async' => array(),
			),
			'#comment' => array(),
		);

		echo wp_kses( $this->get_pixel_script(), $allowed_tags );
	}

	/**
	 * Adds personalization to the pixel script based on the current user.
	 *
	 * If the user is logged in, injects their email address into the script for
	 * more accurate audience tracking. If not logged in, removes placeholder elements.
	 *
	 * @since 0.1.0
	 *
	 * @param string $script Raw pixel JavaScript with placeholders.
	 * @return string Personalized pixel script.
	 */
	protected static function personalize_tracking_script( string $script ): string {
		// @todo: use this once we integrate with Consent API.
		if ( 0 && is_user_logged_in() ) { // for future use.
			$user       = wp_get_current_user();
			$user_email = $user->user_email;

			// Escape the email for JS safety.
			$escaped_email = esc_js( $user_email );

			// Replace the placeholder with actual email.
			return str_replace(
				"'__INSERT_USER_EMAIL__'",
				"'" . $escaped_email . "'",
				$script
			);
		}

		// If user is not logged in, replace with empty string or remove the key.
		return str_replace(
			"'user_email': '__INSERT_USER_EMAIL__'",
			'',
			$script
		);
	}

	/**
	 * Validates that the pixel script includes the expected Ad Partner URL.
	 *
	 * This ensures the cached script hasn't been tampered with.
	 *
	 * @since 0.1.0
	 *
	 * @param string $script The script HTML string.
	 *
	 * @return bool True if valid, false if tampered.
	 */
	private static function is_valid_pixel_script( string $script ): bool {
		return strpos( $script, PixelDefaults::EXPECTED_SCRIPT_URL ) !== false;
	}

	/**
	 * Retrieves the Snapchat Pixel script, either from cache or the remote API.
	 *
	 * If not cached, it authenticates with Jetpack and queries the Snapchat Ads API for pixel script.
	 * The result is cached in the options store and sanitized before being returned.
	 *
	 * @since 0.1.0
	 *
	 * @return string|null The sanitized pixel script, or null on failure.
	 */
	private function get_pixel_script() {
		$pixel_script = Transients::get( TransientDefaults::PIXEL_SCRIPT );

		if ( $pixel_script && self::is_valid_pixel_script( $pixel_script ) ) {
			return self::personalize_tracking_script( $pixel_script );
		}

		$token = $this->auth->get_auth_header();

		if ( is_wp_error( $token ) ) {
			return null;
		}

		$account_id = Options::get( OptionDefaults::AD_ACCOUNT_ID );
		$path       = sprintf( 'adaccounts/%s/pixels', $account_id );
		$response   = $this->wcs_client->proxy_get( $token, $path );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$data = $response->get_data();

		if ( ! $data ) {
			return null;
		}

		$pixel_script = $data['pixels'][0]['pixel']['pixel_javascript'] ?? null;

		if ( ! $pixel_script ) {
			return null;
		}

		Transients::set( TransientDefaults::PIXEL_SCRIPT, $pixel_script );

		return self::personalize_tracking_script( $pixel_script );
	}
}
