<?php
/**
 * Consent handler for marketing tracking.
 *
 * This class provides a static interface for checking user consent
 * for marketing-related tracking, using the WordPress Consent API.
 * If the Consent API is not available, it fails open by assuming consent is granted.
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
 * using the WordPress Consent API. Fails open by returning `true` if the API
 * is not present (e.g., no consent management plugin is installed).
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
	 * installed), the method defaults to assuming consent is granted (fail-open).
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if consent is granted or undetermined; false if explicitly denied.
	 */
	public static function has_marketing_consent(): bool {
		if ( function_exists( 'wp_has_consent' ) ) {
			return wp_has_consent( 'marketing' );
		}

		// Consent API not present — assume consent granted (fail-open).
		return true;
	}
}
