<?php
/**
 * Consent handler for marketing tracking.
 *
 * This class provides a static interface for checking user consent
 * for marketing-related tracking, using the WordPress Consent API.
 * If the Consent API is not available, it fails safely by denying consent.
 *
 * Intended for use before initializing or firing marketing tracking scripts.
 *
 * @package SnapchatForWooCommerce\Tracking
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Tracking;

/**
 * Static interface for checking marketing consent.
 *
 * Checks whether the current visitor has granted consent for marketing tracking,
 * using the WordPress Consent API. Fails safely by returning `false` if the API
 * is not present or the user has not explicitly granted consent.
 *
 * Designed to be used in tracking logic and script enqueuing conditions.
 *
 * @since 0.1.0
 */
final class Consent {

	/**
	 * Determines whether the visitor has granted marketing consent.
	 *
	 * Uses the WordPress Consent API to verify if the visitor has opted into
	 * marketing tracking. If the API is not available (e.g., no Consent plugin
	 * installed), the method defaults to assuming no consent.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if consent is granted; false otherwise.
	 */
	public static function has_marketing_consent(): bool {
		if ( function_exists( 'wp_has_consent' ) ) {
			return wp_has_consent( 'marketing' );
		}

		// Consent API not present — assume consent denied (fail-safe).
		return false;
	}
}
