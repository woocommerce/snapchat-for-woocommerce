<?php
/**
 * Default values used for pixel tracking operations.
 *
 * This class defines constants specific to the Ad Partner's pixel implementation,
 * such as the expected script URL. These values are used internally for validation
 * and processing of remote tracking scripts.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

/**
 * Provides default constants for pixel tracking behavior.
 *
 * This final class contains static values that define expected defaults used
 * in validating and handling the Ad Partner’s pixel. By isolating these values,
 * the system remains adaptable to future changes.
 *
 * @since 0.1.0
 */
final class PixelDefaults {
	/**
	 * Expected CDN URL for the Ad Partner’s pixel tracking script.
	 *
	 * This constant defines the exact URL from which the official tracking script
	 * is expected to load. It is used primarily in validation routines to ensure
	 * that cached or injected pixel scripts have not been altered or replaced.
	 *
	 * Changing this value requires coordination with the Ad Partner, and it should
	 * never be exposed to end-users for editing.
	 *
	 * Used in:
	 * - {@see \SnapchatForWooCommerce\Tracking\RemotePixelTracker::is_valid_pixel_script}
	 *
	 * @since 0.1.0
	 */
	const EXPECTED_SCRIPT_URL = 'https://sc-static.net/scevent.min.js';
}
