<?php
/**
 * Shared onboarding / connection status checks.
 *
 * @package SnapchatForWooCommerce\Utils
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils;

use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Helpers for setup state used by admin UI and meta boxes.
 *
 * @since 0.1.0
 */
final class OnboardingStatus {

	/**
	 * Whether Snapchat onboarding is complete (connected to Snapchat).
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public static function is_setup_completed(): bool {
		return 'connected' === Options::get( OptionDefaults::ONBOARDING_STATUS );
	}
}
