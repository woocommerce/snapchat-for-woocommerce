<?php

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Utils\OptionsStore;
use SnapchatForWooCommerce\Utils\OptionDefaults;

final class RemotePixelTracker implements PixelTracker {

	private WcsClient $wcs_client;
	private JetpackAuthenticator $auth;

	public function __construct( WcsClient $wcs_client, JetpackAuthenticator $auth ) {
		$this->wcs_client = $wcs_client;
		$this->auth       = $auth;
	}

	public function maybe_inject_pixel(): void {
		if ( ! OptionsStore::get( OptionDefaults::PIXEL_ENABLED ) ) {
			return;
		}

		echo $this->get_pixel_script(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	protected static function personalize_tracking_script( string $script ): string {
		if ( is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_email = $user->user_email;

			// Escape the email for JS safety
			$escaped_email = esc_js( $user_email );

			// Replace the placeholder with actual email
			return str_replace(
				"'__INSERT_USER_EMAIL__'",
				"'" . $escaped_email . "'",
				$script
			);
		}

		// If user is not logged in, replace with empty string or remove the key
		return str_replace(
			"'user_email': '__INSERT_USER_EMAIL__'",
			'',
			$script
		);
	}

	private function get_pixel_script() {
		$allowed_tags = array(
			'script' => array(
				'type'  => array(),
				'src'   => array(),
				'async' => array(),
			),
			'#comment' => array(),
		);

		$pixel_script = OptionsStore::get( OptionDefaults::PIXEL_SCRIPT );

		if ( $pixel_script ) {
			return wp_kses( self::personalize_tracking_script( $pixel_script ), $allowed_tags );
		}

		$token = $this->auth->get_auth_header();

		if ( is_wp_error( $token ) ) {
			return null;
		}

		$account_id = OptionsStore::get( OptionDefaults::AD_ACCOUNT_ID );
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

		OptionsStore::set( OptionDefaults::PIXEL_SCRIPT, $pixel_script );

		return wp_kses( self::personalize_tracking_script( $pixel_script ), $allowed_tags );
	}
}
