<?php
/**
 * Main plugin bootstrap class for the Ad Partner integration.
 *
 * Responsible for setting up localization, hooks, and REST routes.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

use SnapchatForWooCommerce\Admin;

/**
 * Initializes and wires up core components of the Ad Partner for WooCommerce plugin.
 *
 * This class is responsible for:
 * - Loading translations.
 * - Registering core WordPress hooks.
 * - Initializing and routing service handlers (e.g., connection and tracking).
 */
final class Plugin {

	/**
	 * Entry point for plugin initialization.
	 *
	 * Loads text domain and registers plugin-level hooks.
	 */
	public static function init(): void {
		self::load_textdomain();
		self::register_hooks();
	}

	/**
	 * Loads the plugin’s translation files from the `languages` directory.
	 *
	 * This enables localization support via `.pot`/`.mo` files.
	 *
	 * @since 0.1.0
	 */
	private static function load_textdomain(): void {
		load_plugin_textdomain( 'snapchat-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages' );
	}

	/**
	 * Registers WordPress hooks used by the plugin.
	 *
	 * Hooks registered:
	 * - `rest_api_init` to load REST routes.
	 * - `init` to initialize plugin features.
	 *
	 * @since 0.1.0
	 */
	private static function register_hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
		add_action( 'init', array( self::class, 'bootstrap_features' ) );
	}

	/**
	 * Registers REST API routes for plugin features.
	 *
	 * Delegates route registration to the appropriate service handlers:
	 * - Connection service
	 * - Pixel tracking service
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function register_rest_routes(): void {
		$connection = ServiceContainer::get( ServiceKey::CONNECTION );
		$connection->register_routes();
	}

	/**
	 * Initializes additional feature hooks during the `init` action.
	 *
	 * @since 0.1.0
	 */
	public static function bootstrap_features(): void {
		( new Assets() )->register_hooks();

		ServiceContainer::get( ServiceKey::PIXEL_TRACKING )->register_hooks();
		ServiceContainer::get( ServiceKey::CONVERSION_TRACKING )->register_hooks();

		( new Admin\Setup(
			new Admin\Menu(),
			new Admin\Assets(),
			new Admin\Onboarding(),
		) )->init();
	}
}
