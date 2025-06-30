<?php
/**
 * Initializes all settings-related REST controllers for the Ad Partner plugin.
 *
 * This class acts as the central router for registering plugin-specific REST endpoints,
 * primarily under the `wc/sfw/<ad_partner>` namespace.
 *
 * Controllers are instantiated with their required dependencies here and hooked into the REST API lifecycle.
 *
 * @package SnapchatForWooCommerce\Admin\Settings
 */

namespace SnapchatForWooCommerce\Admin\Settings;

use Automattic\Jetpack\Connection\Manager;
use SnapchatForWooCommerce\ServiceContainer;
use SnapchatForWooCommerce\ServiceKey;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Config;

/**
 * Bootstrap class for registering REST API routes related to plugin settings.
 *
 * Instantiates individual controllers and registers their routes.
 *
 * @since 0.1.0
 */
class ControllerSetup {

	/**
	 * Registers all REST API routes used in the plugin settings.
	 *
	 * This method is typically called during the `rest_api_init` hook.
	 *
	 * Example:
	 * ```php
	 * add_action( 'rest_api_init', array( new ControllerSetup(), 'register_routes' ) );
	 * ```
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$wcs_client = ServiceContainer::get( ServiceKey::WCS_CLIENT );
		$manager    = new Manager( Config::PLUGIN_SLUG );

		( new JetpackAccountController( $wcs_client, $manager ) )->register_routes();
		( new SnapchatAccountController( $wcs_client ) )->register_routes();
		( new SnapchatOrganizationsController( $wcs_client ) )->register_routes();
		( new SnapchatAdAccountsController() )->register_routes();
		( new SnapchatSnapPixelController( $wcs_client ) )->register_routes();
	}
}
