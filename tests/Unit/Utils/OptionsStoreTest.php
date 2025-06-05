<?php
/**
 * Integration tests for the OptionsStore utility class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration\Utils
 */

namespace SnapchatForWooCommerce\Tests\Integration\Utils;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\OptionDefaults;
use SnapchatForWooCommerce\Utils\OptionsStore;

/**
 * Integration tests for \SnapchatForWooCommerce\Utils\OptionsStore.
 */
class OptionsStoreTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		OptionDefaults::set_prefix( Config::OPTION_PREFIX );
	}

	public function tearDown(): void {
		// Clean up all relevant options.
		foreach ( OptionDefaults::get_defaults() as $key => $_ ) {
			delete_option( OptionDefaults::key( $key ) );
		}
		parent::tearDown();
	}

	public function test_get_returns_existing_option() {
		update_option( 'snapchat_ads_pixel_enabled', true );
		$this->assertTrue(
			OptionsStore::get( OptionDefaults::PIXEL_ENABLED )
		);
	}

	public function test_get_returns_default_if_option_missing() {
		delete_option( 'snapchat_ads_pixel_enabled' );
		$this->assertFalse(
			OptionsStore::get( OptionDefaults::PIXEL_ENABLED )
		);
	}

	public function test_set_calls_update_option() {
		OptionsStore::set( OptionDefaults::PIXEL_ENABLED, true );
		$this->assertTrue(
			get_option( 'snapchat_ads_pixel_enabled' )
		);

		OptionsStore::set( OptionDefaults::PIXEL_ENABLED, false );
		$this->assertFalse(
			get_option( 'snapchat_ads_pixel_enabled' )
		);
	}

	public function test_delete_calls_delete_option() {
		update_option( 'snapchat_ads_pixel_enabled', true );
		OptionsStore::delete( OptionDefaults::PIXEL_ENABLED );
		$this->assertFalse(
			get_option( 'snapchat_ads_pixel_enabled', false )
		);
	}

	public function test_preload_defaults_only_sets_unset_options() {
		// Preload should set all defaults.
		OptionsStore::preload_defaults();
		$defaults = OptionDefaults::get_defaults();

		foreach ( $defaults as $key => $value ) {
			$this->assertSame(
				$value,
				get_option( OptionDefaults::key( $key ) )
			);
		}
	}
}
