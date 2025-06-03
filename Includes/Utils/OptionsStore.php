<?php
/**
 * Option store for reading and writing Ad Partner plugin settings.
 *
 * Provides static helpers for interacting with prefixed WordPress options
 * that belong to the Ad Partner integration.
 *
 * @package SnapchatForWooCommerce\Utils
 */
namespace SnapchatForWooCommerce\Utils;

/**
 * Static utility class for managing prefixed WordPress options used by the Ad Partner plugin.
 *
 * This class abstracts WordPress's native option functions to ensure consistent usage of
 * prefixed keys and default values as defined in {@see OptionDefaults}.
 */
class OptionsStore {

	/**
	 * Retrieves the value of a plugin option, falling back to the default if not set.
	 *
	 * @param string $key The option key (without prefix).
	 *
	 * @return mixed The option value or default.
	 */
	public static function get( string $key ) {
		$defaults = OptionDefaults::get_defaults();
		$default  = array_key_exists( $key, $defaults ) ? $defaults[ $key ] : null;

		return get_option( OptionDefaults::get_prefix() . $key, $default );
	}

	/**
	 * Sets the value of a plugin option.
	 *
	 * @param string $key   The option key (without prefix).
	 * @param mixed  $value The value to store.
	 *
	 * @return void
	 */
	public static function set( string $key, $value ): void {
		update_option( OptionDefaults::get_prefix() . $key, $value );
	}

	/**
	 * Deletes a plugin option.
	 *
	 * @param string $key The option key (without prefix).
	 *
	 * @return void
	 */
	public static function delete( string $key ): void {
		delete_option( OptionDefaults::get_prefix() . $key );
	}

	/**
	 * Ensures all default options are initialized if they are not already set.
	 *
	 * This is useful during plugin activation or upgrade processes.
	 *
	 * @return void
	 */
	public static function preload_defaults(): void {
		foreach ( OptionDefaults::get_defaults() as $key => $value ) {
			if ( get_option( OptionDefaults::get_prefix() . $key, null ) === null ) {
				update_option( OptionDefaults::get_prefix() . $key, $value );
			}
		}
	}
}
