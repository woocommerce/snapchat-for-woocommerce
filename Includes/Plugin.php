<?php

namespace SnapchatForWooCommerce;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/**
	 * Initializes the plugin lifecycle.
	 */
	public static function init() {
		self::load_textdomain();
		self::register_hooks();
	}

	/**
	 * Load plugin translations.
	 */
	private static function load_textdomain() {
		load_plugin_textdomain( 'snapchat-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages' );
	}

	/**
	 * Register all core hooks.
	 */
	private static function register_hooks() {
		add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );
		add_action( 'init', [ self::class, 'bootstrap_features' ] );
	}

	/**
	 * Register all REST API routes via service classes.
	 */
	public static function register_rest_routes() {
		// Lazy-load only once per service
		$connection = ServiceContainer::get( 'connection' );
		$connection->register_routes();

		$pixel = ServiceContainer::get( 'pixel_tracking' );
		$pixel->register_routes();
	}

	/**
	 * Bootstrap non-REST features like frontend pixel injection.
	 */
	public static function bootstrap_features() {
		ServiceContainer::get( 'pixel_tracking' )->register_hooks();
	}
}
