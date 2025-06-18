<?php
/**
 * Interface for abstracting different storage mechanisms.
 *
 * Defines a common contract for reading, writing, and deleting
 * values from a storage backend (e.g., WordPress options, transients).
 *
 * This interface enables pluggable storage strategies to be injected
 * into a generic {@see Store} class that operates uniformly on any
 * storage type.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

/**
 * Defines a contract for interacting with a key-value storage backend.
 *
 * This interface is used by the Ad Partner plugin to abstract how settings
 * are persisted—whether via WordPress options, transients, or another mechanism.
 * It allows a generic {@see Store} class to delegate all reads and writes
 * without being tied to a specific storage type.
 *
 * Implementations must ensure all keys are prefixed consistently and that
 * data is stored and retrieved using WordPress APIs or equivalents.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */
interface StorageStrategyInterface {

	/**
	 * Retrieves a value from the storage backend.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The storage key (without prefix).
	 * @return mixed The stored value, or false if not found.
	 */
	public function get( string $key );

	/**
	 * Writes a value to the storage backend.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key   The storage key (without prefix).
	 * @param mixed  $value The value to store.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value ): bool;

	/**
	 * Deletes a value from the storage backend.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The storage key (without prefix).
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key ): bool;
}
