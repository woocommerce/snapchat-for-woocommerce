<?php
/**
 * Extracts and formats user identifiers for CAPI matching.
 *
 * Gathers client IP, user agent, and optionally hashes email and phone number.
 *
 * @package SnapchatForWooCommerce\Utils
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils;

/**
 * Builds the user_data structure for CAPI event payloads.
 *
 * This includes hashed personally identifiable information (PII)
 * and device metadata to improve match rates for the Ad Partner.
 *
 * @since 0.1.0
 */
final class UserIdentifier {

	/**
	 * Returns a user_data array for CAPI payloads.
	 *
	 * Includes hashed email and phone (if available),
	 * along with IP address and user agent string.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> Associative array of user identifiers.
	 */
	public static function get_user_data(): array {
		$data = array();
		$ip   = '';

		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$raw  = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$list = explode( ',', $raw );
			$ip   = trim( $list[0] );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( $ip ) {
			$data['client_ip_address'] = $ip;
		}

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$data['client_user_agent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		return $data;
	}
}
