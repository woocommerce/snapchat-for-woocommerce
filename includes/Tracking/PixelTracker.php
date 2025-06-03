<?php
namespace SnapchatForWoocommerce\Tracking;

use SnapchatForWoocommerce\Infrastructure\WcsClient;
use SnapchatForWoocommerce\Infrastructure\JetpackAuthenticator;
use SnapchatForWoocommerce\Config\AdPartnerProxyPathProvider;
use SnapchatForWoocommerce\Config\OptionDefaults;
use SnapchatForWoocommerce\Config\OptionsStore;

class PixelTracker extends RemotePixelTracker {

	private WcsClient $wcs;
	private JetpackAuthenticator $auth;
	private string $ad_account_id;

	public function __construct( WcsClient $wcs, JetpackAuthenticator $auth, string $ad_account_id ) {
		$this->wcs = $wcs;
		$this->auth = $auth;
		$this->ad_account_id = $ad_account_id;
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

	protected function get_tracking_snippet(): ?string {
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

		$url = $this->wcs->get_url_for(
			AdPartnerProxyPathProvider::get_path( 'pixel', [ 'ad_account_id' => $this->ad_account_id ] ),
		);

		$response = wp_remote_get( $url, [
			'headers' => [
				'X_JP_Auth' => $this->auth->get_auth_header(),
			],
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$data         = json_decode( wp_remote_retrieve_body( $response ), true );
		$pixel_script = $data['pixels'][0]['pixel']['pixel_javascript'] ?? null;

		if ( ! $pixel_script ) {
			return null;
		}

		OptionsStore::set( OptionDefaults::PIXEL_SCRIPT, $pixel_script );

		return wp_kses( self::personalize_tracking_script( $pixel_script ), $allowed_tags );
	}
}
