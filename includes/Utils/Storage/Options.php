<?php
/**
 * Static facade for reading and writing Ad Partner plugin options.
 *
 * This class provides a centralized, static interface to interact with
 * WordPress options used by the Ad Partner integration. It wraps a
 * reusable Store instance with an OptionStorage strategy and
 * default fallbacks from {@see OptionDefaults}.
 *
 * Designed for convenience and consistency across plugin logic.
 *
 * @package SnapchatForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Utils\Storage;

use SnapchatForWooCommerce\Config;

/**
 * Static interface for accessing Ad Partner plugin options.
 *
 * This class serves as a centralized facade for reading, writing,
 * and initializing WordPress options tied to the Ad Partner integration.
 * Internally, it wraps a {@see Store} configured with {@see OptionStorage}
 * and default values from {@see OptionDefaults}.
 *
 * Useful in procedural code and hooks where dependency injection is not practical.
 *
 * @since 0.1.0
 */
final class Options {

	/**
	 * Singleton instance of the Store using OptionStorage.
	 *
	 * @var Store|null
	 */
	private static ?Store $instance = null;

	/**
	 * Lazily instantiates the store with the OptionStorage strategy.
	 *
	 * @since 0.1.0
	 *
	 * @return Store The configured store instance.
	 */
	private static function get_instance(): Store {
		if ( null === self::$instance ) {
			self::$instance = new Store(
				new OptionStorage(),
				OptionDefaults::get_all()
			);
		}
		return self::$instance;
	}

	/**
	 * Retrieves the value of an Ad Partner plugin option.
	 *
	 * Falls back to the default defined in {@see OptionDefaults}
	 * if the value is not stored in the database.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The option key (without prefix).
	 * @return mixed The option value or default.
	 */
	public static function get( string $key ) {
		return self::get_instance()->get( $key );
	}

	/**
	 * Sets the value of an Ad Partner plugin option.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key   The option key (without prefix).
	 * @param mixed  $value The value to store.
	 * @return bool True on success, false on failure.
	 */
	public static function set( string $key, $value ): bool {
		return self::get_instance()->set( $key, $value );
	}

	/**
	 * Deletes an Ad Partner plugin option.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The option key (without prefix).
	 * @return bool True on success, false on failure.
	 */
	public static function delete( string $key ): bool {
		return self::get_instance()->delete( $key );
	}

	/**
	 * Returns the full option key with the plugin prefix.
	 *
	 * This is used to ensure consistent key formatting across the plugin.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The option key (without prefix).
	 * @return string The full option key with prefix.
	 */
	public static function get_key( string $key ): string {
		return Config::STORE_PREFIX . $key;
	}

	/**
	 * Initializes all defined option keys with default values, if not already set.
	 *
	 * Should be called during plugin activation to ensure consistent defaults.
	 * This bypasses fallback logic to check raw database state directly.
	 *
	 * @since 0.1.0
	 */
	public static function preload_defaults(): void {
		foreach ( OptionDefaults::get_all() as $key => $value ) {
			$full_key = Config::STORE_PREFIX . $key;

			if ( get_option( $full_key, null ) === null ) {
				update_option( $full_key, $value );
			}
		}
	}
}
