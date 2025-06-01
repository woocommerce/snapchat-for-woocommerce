<?php

namespace SnapchatForWoocommerce\Config;

class OptionDefaults {
	private static string $prefix = '';
	public const AD_ACCOUNT_ID    = 'ad_account_id';
	public const ORGANIZATION_ID  = 'organization_id';
	public const PIXEL_ENABLED    = 'ads_pixel_enabled';

	public static function set_prefix( string $prefix ): void {
		self::$prefix = rtrim( $prefix, '_' ) . '_';
	}

	public static function get_prefix(): string {
		return self::$prefix;
	}

	public static function key( string $suffix ): string {
		return self::$prefix . $suffix;
	}

	/**
	 * Returns an associative array of option keys => default values.
	 */
	public static function get_defaults(): array {
		return [
			self::AD_ACCOUNT_ID      => '',
			self::ORGANIZATION_ID => '',
			self::PIXEL_ENABLED   => false,
		];
	}
}
