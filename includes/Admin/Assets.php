<?php
/**
 * Enqueues admin-specific assets for Snapchat for WooCommerce.
 *
 * This class is responsible for loading JavaScript and CSS assets
 * used within the WordPress admin interface. Assets are only loaded
 * on admin pages and are expected to be registered using the plugin’s
 * shared {@see AssetLoader} utility.
 *
 * @package SnapchatForWooCommerce\Admin
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin;

use SnapchatForWooCommerce\Utils\AssetLoader;

/**
 * Handles admin script and style enqueues.
 *
 * Registers the `admin_enqueue_scripts` action to load plugin-specific
 * admin assets such as JavaScript for UI components or custom styles
 * for Gutenberg integrations or settings pages.
 *
 * @since 0.1.0
 */
class Assets {

	/**
	 * Registers WordPress admin-side hooks.
	 *
	 * Hooks into `admin_enqueue_scripts` to enqueue plugin-specific admin scripts and styles.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueues plugin admin assets (JS/CSS).
	 *
	 * Uses the shared {@see AssetLoader} utility to enqueue assets registered with
	 * handles prefixed as `admin`. These assets must be defined in the plugin’s
	 * asset manifest or registered via `wp_register_script()` / `wp_register_style()`.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		AssetLoader::enqueue_script( 'admin', 'admin' );
		AssetLoader::enqueue_style( 'admin', 'admin' );
	}
}
