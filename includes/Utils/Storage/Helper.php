<?php
/**
 * Utility helper methods for interacting with Ad Partner plugin options.
 *
 * Provides convenience wrappers for common option checks and transformations.
 * This class centralizes logic related to frequently used option values,
 * ensuring consistent behavior across the codebase.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

/**
 * Static utility methods for option handling.
 *
 * Contains reusable helper functions that operate on WordPress options
 * defined in {@see OptionDefaults}. These methods simplify checking
 * and interpreting stored values.
 *
 * @since 0.1.0
 */
final class Helper {

	/**
	 * Determines whether Personally Identifiable Information (PII) collection is enabled.
	 *
	 * Checks the {@see OptionDefaults::COLLECT_PII} option to verify if PII
	 * collection is currently allowed. Returns `true` if enabled, `false` otherwise.
	 *
	 * @since 0.1.0
	 *
	 * @return bool Whether PII collection is enabled.
	 */
	public static function is_collect_pii_enabled(): bool {
		return 'yes' === Options::get( OptionDefaults::COLLECT_PII );
	}
}
