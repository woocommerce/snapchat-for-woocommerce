<?php
/**
 * Option-based storage strategy for Ad Partner plugin settings.
 *
 * Implements the {@see StorageStrategyInterface} interface using WordPress options
 * as the underlying persistence layer. This class ensures all keys are
 * consistently prefixed using {@see Config::STORE_PREFIX}.
 *
 * Intended for use within the Store abstraction to support pluggable
 * storage mechanisms for the Ad Partner plugin.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

use SnapchatForWooCommerce\Config;

/**
 * Stores Ad Partner plugin settings using the WordPress options API.
 *
 * This class implements {@see StorageStrategyInterface} and persists values
 * in the `wp_options` table. All keys are automatically prefixed using
 * {@see Config::STORE_PREFIX} to avoid collisions.
 *
 * Intended to be used with the {@see Store} abstraction to support consistent
 * read/write operations across different storage backends.
 *
 * @since 0.1.0
 */
final class OptionStorage implements StorageStrategyInterface {

	/**
	 * Retrieves a value from the WordPress options table.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The option key (without prefix).
	 * @return mixed The stored value, or false if not found.
	 */
	public function get( string $key ) {
		return get_option( Config::STORE_PREFIX . $key );
	}

	/**
	 * Stores a value in the WordPress options table.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key   The option key (without prefix).
	 * @param mixed  $value The value to store.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value ): bool {
		return update_option( Config::STORE_PREFIX . $key, $value );
	}

	/**
	 * Deletes a value from the WordPress options table.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The option key (without prefix).
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key ): bool {
		return delete_option( Config::STORE_PREFIX . $key );
	}
}
