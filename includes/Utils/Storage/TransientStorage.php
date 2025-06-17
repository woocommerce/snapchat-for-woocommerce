<?php
/**
 * Transient-based storage strategy for Ad Partner plugin settings.
 *
 * Implements the {@see StorageStrategyInterface} interface using WordPress transients
 * as the underlying persistence layer. All keys are prefixed using
 * {@see Config::STORE_PREFIX}, and expiration is determined via
 * {@see TransientDefaults::get_ttl()}.
 *
 * Intended for use within the {@see Store} abstraction to support pluggable
 * caching mechanisms for the Ad Partner plugin.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * Stores Ad Partner plugin data using the WordPress transients API.
 *
 * This class implements {@see StorageStrategyInterface} and caches values
 * in the transient system with automatic expiration. TTLs are resolved
 * via {@see TransientDefaults}, and all keys are prefixed consistently
 * to avoid collisions.
 *
 * Intended to be used with the {@see Store} abstraction to support
 * time-sensitive or performance-critical storage needs.
 *
 * @since 0.1.0
 */
final class TransientStorage implements StorageStrategyInterface {

	/**
	 * Retrieves a value from the WordPress transients API.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The transient key (without prefix).
	 * @return mixed The cached value, or false if not found or expired.
	 */
	public function get( string $key ) {
		return get_transient( Config::STORE_PREFIX . $key );
	}

	/**
	 * Stores a value in the WordPress transients API with a TTL.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key   The transient key (without prefix).
	 * @param mixed  $value The value to cache.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value ): bool {
		$ttl = TransientDefaults::get_ttl( $key );
		return set_transient( Config::STORE_PREFIX . $key, $value, $ttl );
	}

	/**
	 * Deletes a value from the WordPress transients API.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The transient key (without prefix).
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key ): bool {
		return delete_transient( Config::STORE_PREFIX . $key );
	}
}
