<?php
/**
 * Extracts and formats user identifiers for CAPI matching.
 *
 * Gathers client IP, user agent, Snapchat click ID (ScCid),
 * and sc_cookie1 for better attribution in server-side events.
 *
 * @package SnapchatForWooCommerce\Utils
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils;

/**
 * Builds the user_data structure for CAPI event payloads.
 *
 * This includes device metadata (IP, UA), Snapchat cookies, and click ID data.
 * Intended to improve event match rate and attribution accuracy.
 *
 * @since 0.1.0
 */
final class UserIdentifier {

	/**
	 * Returns a user_data array for CAPI payloads.
	 *
	 * Includes:
	 * - IP address and user agent (from server)
	 * - `sc_click_id` from cookie or session
	 * - `sc_cookie1` from `_scid` cookie
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

		if ( isset( $_COOKIE['_scid'] ) ) {
			$data['sc_cookie1'] = sanitize_text_field( wp_unslash( $_COOKIE['_scid'] ) );
		}

		if ( isset( $_COOKIE['ScCid'] ) ) {
			$data['sc_click_id'] = sanitize_text_field( wp_unslash( $_COOKIE['ScCid'] ) );
		}

		return $data;
	}
}
