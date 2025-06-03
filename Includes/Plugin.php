<?php
/**
 * Main plugin bootstrap class for the Ad Partner integration.
 *
 * Responsible for setting up localization, hooks, and REST routes.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\OptionDefaults;

defined( 'ABSPATH' ) || exit;

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
	 *
	 * @return void
	 */
	public static function init() {
		OptionDefaults::set_prefix( Config::OPTION_PREFIX );
		self::load_textdomain();
		self::register_hooks();
	}

	/**
	 * Loads the plugin’s translation files from the `languages` directory.
	 *
	 * This enables localization support via `.pot`/`.mo` files.
	 *
	 * @return void
	 */
	private static function load_textdomain() {
		load_plugin_textdomain( 'snapchat-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages' );
	}

	/**
	 * Registers WordPress hooks used by the plugin.
	 *
	 * Hooks registered:
	 * - `rest_api_init` to load REST routes.
	 * - `init` to initialize plugin features.
	 *
	 * @return void
	 */
	private static function register_hooks() {
		add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );
		add_action( 'init', [ self::class, 'bootstrap_features' ] );
	}

	/**
	 * Registers REST API routes for plugin features.
	 *
	 * Delegates route registration to the appropriate service handlers:
	 * - Connection service
	 * - Pixel tracking service
	 *
	 * @return void
	 */
	public static function register_rest_routes() {
		// Lazy-load only once per service
		$connection = ServiceContainer::get( 'connection' );
		$connection->register_routes();

		$pixel = ServiceContainer::get( 'pixel_tracking' );
		$pixel->register_routes();
	}

	/**
	 * Initializes additional feature hooks during the `init` action.
	 *
	 * Currently boots pixel tracking hooks.
	 *
	 * @return void
	 */
	public static function bootstrap_features() {
		ServiceContainer::get( 'pixel_tracking' )->register_hooks();
	}
}
