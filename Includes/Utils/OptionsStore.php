<?php
namespace SnapchatForWooCommerce\Utils;

class OptionsStore {
	public static function get( string $key ) {
		$defaults = OptionDefaults::get_defaults();
		$default  = array_key_exists( $key, $defaults ) ? $defaults[ $key ] : null;

		return get_option( OptionDefaults::get_prefix() . $key, $default );
	}

	public static function set( string $key, $value ): void {
		update_option( OptionDefaults::get_prefix() . $key, $value );
	}

	public static function delete( string $key ): void {
		delete_option( OptionDefaults::get_prefix() . $key );
	}

	public static function preload_defaults(): void {
		foreach ( OptionDefaults::get_defaults() as $key => $value ) {
			if ( get_option( OptionDefaults::get_prefix() . $key, null ) === null ) {
				update_option( OptionDefaults::get_prefix() . $key, $value );
			}
		}
	}
}
