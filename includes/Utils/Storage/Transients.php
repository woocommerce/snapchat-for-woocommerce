<?php
/**
 * Static facade for reading and writing Ad Partner plugin transients.
 *
 * This class provides a centralized, static interface to interact with
 * WordPress transients used by the Ad Partner integration. It wraps a
 * reusable Store instance with a TransientStorage strategy and
 * expiration durations from {@see TransientDefaults}.
 *
 * Designed for convenience and consistency across plugin logic.
 *
 * @package SnapchatForWooCommerce\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

use SnapchatForWooCommerce\Utils\Storage\TransientStorage;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Storage\Store;

/**
 * Static interface for accessing Ad Partner plugin transients.
 *
 * This class serves as a centralized facade for reading, writing,
 * and deleting WordPress transients tied to the Ad Partner integration.
 * Internally, it wraps a {@see Store} configured with {@see TransientStorage}
 * and expiration values from {@see TransientDefaults}.
 *
 * Useful in procedural code and hooks where dependency injection is not practical.
 *
 * @since 0.1.0
 */
final class Transients {

	/**
	 * Singleton instance of the Store using TransientStorage.
	 *
	 * @var Store|null
	 */
	private static ?Store $instance = null;

	/**
	 * Lazily instantiates the store with the TransientStorage strategy.
	 *
	 * @since 0.1.0
	 *
	 * @return Store The configured store instance.
	 */
	private static function get_instance(): Store {
		if ( null === self::$instance ) {
			self::$instance = new Store(
				new TransientStorage(),
				TransientDefaults::get_all()
			);
		}
		return self::$instance;
	}

	/**
	 * Retrieves the value of an Ad Partner plugin transient.
	 *
	 * Falls back to the default defined in {@see TransientDefaults}
	 * if the value is not cached or has expired.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The transient key (without prefix).
	 * @return mixed The transient value or default.
	 */
	public static function get( string $key ) {
		return self::get_instance()->get( $key );
	}

	/**
	 * Sets the value of an Ad Partner plugin transient.
	 *
	 * Expiration is automatically determined using {@see TransientDefaults}.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key   The transient key (without prefix).
	 * @param mixed  $value The value to store.
	 * @return bool True on success, false on failure.
	 */
	public static function set( string $key, $value ): bool {
		return self::get_instance()->set( $key, $value );
	}

	/**
	 * Deletes an Ad Partner plugin transient.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The transient key (without prefix).
	 * @return bool True on success, false on failure.
	 */
	public static function delete( string $key ): bool {
		return self::get_instance()->delete( $key );
	}
}
