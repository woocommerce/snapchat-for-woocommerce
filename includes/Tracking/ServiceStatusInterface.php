<?php
/**
 * Interface for services that support runtime activation checks.
 *
 * This interface defines a common contract for determining whether
 * a given feature or service is currently enabled, based on plugin
 * options or contextual configuration.
 *
 * It allows services like Pixel or CAPI tracking to expose their
 * active/inactive status in a consistent way.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

/**
 * Declares a common method for checking if a tracking or integration
 * service is currently enabled.
 *
 * Implementing classes must define a static `is_enabled()` method
 * to report their operational status.
 *
 * @since 0.1.0
 */
interface ServiceStatusInterface {
	/**
	 * Indicates whether the service is enabled.
	 *
	 * Typically this involves checking a plugin option or runtime condition.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if the service is active; false otherwise.
	 */
	public static function is_enabled(): bool;
}
