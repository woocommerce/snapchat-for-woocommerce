<?php
namespace SnapchatForWooCommerce\Connection;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class OAuthState {
	public static function encode( array $data ): string {
		return rawurlencode( base64_encode( wp_json_encode( $data ) ) );
	}

	public static function decode( string $state ) {
		$decoded = base64_decode( rawurldecode( $state ), true );
		$data    = json_decode( $decoded, true );

		if ( ! is_array( $data ) || json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'invalid_state', 'Could not decode OAuth state.' );
		}

		return $data;
	}
}
