<?php
namespace SnapchatForWoocommerce\Tracking;

use SnapchatForWoocommerce\Infrastructure\WcsClient;
use SnapchatForWoocommerce\Infrastructure\JetpackAuthenticator;

class PixelTracker extends RemotePixelTracker {

	private WcsClient $wcs;
	private JetpackAuthenticator $auth;
	private string $ad_account_id;

	public function __construct( WcsClient $wcs, JetpackAuthenticator $auth, string $ad_account_id ) {
		$this->wcs = $wcs;
		$this->auth = $auth;
		$this->ad_account_id = $ad_account_id;
	}

	protected function get_tracking_snippet(): ?string {
		$url = $this->wcs->get_url_for( 'snapchat/snapchat-ads/adaccounts/' . $this->ad_account_id . '/pixels' );

		$response = wp_remote_get( $url, [
			'headers' => [
				// 'X_JP_Auth' => $this->auth->get_auth_header(), // TODO: undo this
				'X_JP_Auth' => ''
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

		return sprintf(
			"<script>(function(w,d,t){/* Snap Pixel Code */})(window,document,'script');snaptr('init', '%s');snaptr('track', 'PAGE_VIEW');</script>",
			esc_js( $pixel_script )
		);
	}
}
