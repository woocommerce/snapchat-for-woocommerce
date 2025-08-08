<?php
/**
 * Main plugin bootstrap class for the Ad Partner integration.
 *
 * Responsible for setting up localization, hooks, and REST routes.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

use SnapchatForWooCommerce\Compatibility;
use SnapchatForWooCommerce\MultichannelMarketing\Marketing;

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
		self::register();
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
	private static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
		self::bootstrap_features();
		self::bootstrap_admin_features();
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
		$settings_controller = ServiceContainer::get( ServiceKey::SETTINGS_REST_CONTROLLER_SETUP );
		$settings_controller->register_routes();
	}

	/**
	 * Initializes feature hooks during the `init` action.
	 *
	 * @since 0.1.0
	 */
	public static function bootstrap_features(): void {
		( new Assets() )->register_hooks();

		ServiceContainer::get( ServiceKey::PIXEL_TRACKING )->register_hooks();
		ServiceContainer::get( ServiceKey::CONVERSION_TRACKING )->register_hooks();
		ServiceContainer::get( ServiceKey::PRODUCT_EXPORT_SERVICE )->register_hooks();
		Marketing::register_hooks();
		Compatibility::register_hooks();
	}

	/**
	 * Initializes admin feature hooks during the `admin_init` action.
	 *
	 * @since 0.1.0
	 */
	public static function bootstrap_admin_features(): void {
		ServiceContainer::get( ServiceKey::ADMIN_SETUP )->init();
	}
}
