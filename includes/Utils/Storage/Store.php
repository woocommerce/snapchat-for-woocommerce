<?php
/**
 * Generic storage handler with pluggable strategy and default fallbacks.
 *
 * This class abstracts the logic for reading, writing, and deleting
 * Ad Partner plugin settings across multiple storage backends (e.g., options, transients).
 * It delegates actual storage to a {@see StorageStrategyInterface} implementation,
 * while providing fallback support using a map of predefined defaults.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

/**
 * Storage abstraction layer for Ad Partner plugin configuration.
 *
 * Uses a {@see StorageStrategyInterface} to handle interaction with the underlying
 * persistence mechanism, such as options or transients. Also provides
 * fallback behavior by consulting a static map of default values.
 *
 * Used by static facades such as {@see Options} and {@see Transients}.
 *
 * @since 0.1.0
 */
final class Store {

	/**
	 * Strategy used to interact with the underlying storage backend.
	 *
	 * @var StorageStrategyInterface
	 */
	private StorageStrategyInterface $strategy;

	/**
	 * Map of default values for supported keys.
	 *
	 * @var array<string,mixed>
	 */
	private array $defaults;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param StorageStrategyInterface $strategy Storage backend implementation.
	 * @param array<string,mixed>      $defaults Map of default values keyed by storage keys.
	 */
	public function __construct( StorageStrategyInterface $strategy, array $defaults ) {
		$this->strategy = $strategy;
		$this->defaults = $defaults;
	}

	/**
	 * Retrieves a value from the underlying storage.
	 *
	 * Falls back to the corresponding default value if the storage
	 * returns false (e.g., key not set).
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The storage key (without prefix).
	 * @return mixed The stored value or the default.
	 */
	public function get( string $key ) {
		$value = $this->strategy->get( $key );
		return false !== $value ? $value : ( $this->defaults[ $key ] ?? null );
	}

	/**
	 * Stores a value using the configured strategy.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key   The storage key (without prefix).
	 * @param mixed  $value The value to store.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value ): bool {
		return $this->strategy->set( $key, $value );
	}

	/**
	 * Deletes a value using the configured strategy.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The storage key (without prefix).
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key ): bool {
		return $this->strategy->delete( $key );
	}
}
