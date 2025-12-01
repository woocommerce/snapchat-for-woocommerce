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
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\ServiceKey;
use SnapchatForWooCommerce\ServiceContainer;
use SnapchatForWooCommerce\Admin\Export\Service\ProductExportService;
use SnapchatForWooCommerce\Utils\Helper;

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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$path = sanitize_text_field( wp_unslash( $_GET['path'] ?? '' ) );

		if ( ! ( 'wc-admin' === $page && str_contains( $path, '/snapchat' ) ) ) {
			return;
		}

		$csv_path = Options::get( OptionDefaults::EXPORT_FILE_PATH );

		AssetLoader::enqueue_script( 'index', 'index' );
		AssetLoader::enqueue_style( 'index', 'index' );
		AssetLoader::localize_script(
			'index',
			'AdminData',
			array(
				'setupComplete'        => boolval( Options::get( OptionDefaults::ONBOARDING_STATUS ) === 'connected' ),
				'status'               => Options::get( OptionDefaults::ONBOARDING_STATUS ),
				'step'                 => Options::get( OptionDefaults::ONBOARDING_STEP ),
				'exportNonce'          => wp_create_nonce( 'export-nonce' ),
				'isExportInProgress'   => ServiceContainer::get( ServiceKey::PRODUCT_EXPORT_SERVICE )->job->is_job_in_progress( ProductExportService::ACTION_HOOK ),
				'exportFileUrl'        => file_exists( $csv_path ) ? Options::get( OptionDefaults::EXPORT_FILE_URL ) : '',
				'lastTimestamp'        => Helper::get_formatted_timestamp( Options::get( OptionDefaults::LAST_EXPORT_TIMESTAMP ) ),
				'slug'                 => 'snapwoo',
				'pluginVersion'        => SNAPCHAT_FOR_WOOCOMMERCE_VERSION,
				'adAccountId'          => Options::get( OptionDefaults::AD_ACCOUNT_ID ),
				'isLegacyPluginActive' => Helper::is_legacy_snapchat_plugin_active(),
			)
		);
	}
}
