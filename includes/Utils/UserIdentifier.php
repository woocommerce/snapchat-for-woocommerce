<?php
/**
 * Extracts and formats user identifiers for Conversions API (CAPI) matching.
 *
 * This utility class normalizes, sanitizes, and hashes personally identifiable
 * information (PII) collected during WooCommerce transactions. The output is
 * used as the `user_data` structure in server-side Conversions API payloads,
 * improving event attribution and match rates.
 *
 * Identifiers are processed according to the Snapchat CAPI specification:
 * - IP address and user agent (raw values, not hashed).
 * - sc_click_id (query param) and sc_cookie1 (pixel cookie).
 * - Customer details (email, phone, first/last name, city, postal code, country).
 *
 * All personal identifiers (em, ph, fn, ln, ct, zp, country) are normalized
 * and hashed with SHA-256, as required by the API spec.
 *
 * @see https://developers.snap.com/api/marketing-api/Conversions-API/Parameters#user-data-parameters
 *
 * @package SnapchatForWooCommerce\Utils
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils;

use SnapchatForWooCommerce\Utils\Storage;

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
	 * Generates the complete `user_data` array used in event payloads.
	 *
	 * Combines IP address, user agent, Snap cookies/click IDs into a
	 * single associative array.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> Associative array of user identifiers.
	 */
	public static function get_user_data(): array {
		$data = array();

		self::add_ip_address( $data );
		self::add_user_agent( $data );
		self::add_sc_cookie( $data );
		self::add_click_id( $data );

		if ( is_order_received_page() && Storage\Helper::is_collect_pii_enabled() ) {
			// ⚠️ Values are only extracted on the WooCommerce order-received page.
			self::add_user_details( $data );
		}

		return $data;
	}

	/**
	 * Adds the Snapchat click ID (sc_click_id) if present.
	 *
	 * Extracted from the `ScCid` cookie set when a user clicks on a Snapchat ad.
	 * Used for click-through attribution.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $data Reference to the user_data array.
	 * @return void
	 */
	private static function add_click_id( array &$data ): void {
		if ( isset( $_COOKIE['ScCid'] ) ) {
			$data['sc_click_id'] = sanitize_text_field( wp_unslash( $_COOKIE['ScCid'] ) );
		}
	}

	/**
	 * Adds the Snapchat cookie (sc_cookie1) if present.
	 *
	 * The `_scid` cookie is set by the Snapchat Pixel on the client side.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $data Reference to the user_data array.
	 * @return void
	 */
	private static function add_sc_cookie( array &$data ): void {
		if ( isset( $_COOKIE['_scid'] ) ) {
			$data['sc_cookie1'] = sanitize_text_field( wp_unslash( $_COOKIE['_scid'] ) );
		}
	}

	/**
	 * Adds the client user agent string.
	 *
	 * Extracted from the current HTTP request. Passed as-is (not hashed).
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $data Reference to the user_data array.
	 * @return void
	 */
	private static function add_user_agent( array &$data ): void {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$data['client_user_agent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}
	}

	/**
	 * Adds the client IP address.
	 *
	 * Respects proxy/CDN headers (Cloudflare, X-Forwarded-For)
	 * and falls back to REMOTE_ADDR. Passed as-is (not hashed).
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed>|null $data Optional. Reference to the user_data array.
	 * @return ?string
	 */
	public static function add_ip_address( ?array &$data = null ): ?string {
		$ip = '';

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
			if ( is_array( $data ) ) {
				$data['client_ip_address'] = $ip;
				return null;
			}

			return $ip;
		}

		return null;
	}

	/**
	 * Extracts and normalizes customer billing details for CAPI matching.
	 *
	 * Supported identifiers:
	 * - em      > Email address (lowercased, trimmed, SHA-256 hashed).
	 * - ph      > Phone number (digits only, SHA-256 hashed).
	 * - fn      > First name (lowercase UTF-8, punctuation removed, SHA-256 hashed).
	 * - ln      > Last name (lowercase UTF-8, punctuation removed, SHA-256 hashed).
	 * - ct      > City (lowercase, punctuation/spaces removed, SHA-256 hashed).
	 * - zp      > Postal code (normalized by country rules, SHA-256 hashed).
	 *              - US: first 5 digits.
	 *              - GB: area + district + sector (excludes unit).
	 * - country > ISO-2 country code (lowercase, SHA-256 hashed).
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $data Reference to the user_data array.
	 */
	public static function add_user_details( array &$data ): void {
		$order_id = (int) get_query_var( 'order-received' );
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$email      = $order->get_billing_email();
		$phone      = $order->get_billing_phone();
		$first_name = $order->get_billing_first_name();
		$last_name  = $order->get_billing_last_name();
		$city       = $order->get_billing_city();
		$zip        = $order->get_billing_postcode();
		$country    = $order->get_billing_country();

		if ( $email ) {
			$normalized = strtolower( trim( $email ) );
			$data['em'] = hash( 'sha256', $normalized );
		}

		if ( $phone ) {
			$normalized = preg_replace( '/\D+/', '', $phone );
			$data['ph'] = hash( 'sha256', strtolower( $normalized ) );
		}

		if ( $first_name ) {
			$normalized = mb_strtolower( $first_name, 'UTF-8' );
			$normalized = preg_replace( '/[[:punct:]]/u', '', $normalized );
			$data['fn'] = hash( 'sha256', $normalized );
		}

		if ( $last_name ) {
			$normalized = mb_strtolower( $last_name, 'UTF-8' );
			$normalized = preg_replace( '/[[:punct:]]/u', '', $normalized );
			$data['ln'] = hash( 'sha256', $normalized );
		}

		if ( $city ) {
			$normalized = mb_strtolower( $city, 'UTF-8' );
			$normalized = preg_replace( '/[[:punct:]\s]+/u', '', $normalized );
			$data['ct'] = hash( 'sha256', $normalized );
		}

		if ( $zip ) {
			$normalized = mb_strtolower( $zip, 'UTF-8' );
			$normalized = preg_replace( '/[\s\-]+/', '', $normalized );

			if ( 'US' === $country ) {
				// US: only first 5 digits.
				$normalized = substr( $normalized, 0, 5 );
			} elseif ( 'GB' === $country ) {
				// UK: area + district + sector (strip unit part)
				// Example: SW1A1AA → sw1a1.
				if ( preg_match( '/^([a-z]{1,2}\d[a-z\d]?\d?)/i', $normalized, $matches ) ) {
					$normalized = strtolower( $matches[1] );
				}
			}

			$data['zp'] = hash( 'sha256', $normalized );
		}

		if ( $country ) {
			$normalized      = strtolower( $country );
			$data['country'] = hash( 'sha256', $normalized );
		}
	}
}
