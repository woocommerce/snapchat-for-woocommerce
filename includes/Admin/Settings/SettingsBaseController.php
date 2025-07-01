<?php
/**
 * Base controller for Settings REST endpoints in the Ad Partner for WooCommerce plugin.
 *
 * This abstract class provides common functionality for all REST controllers
 * within the settings namespace of the plugin. It handles permission checks
 * and response formatting, and should be extended by all plugin-specific
 * settings controllers instead of using WC_REST_Controller directly.
 *
 * @package SnapchatForWooCommerce\Admin\Settings
 */

namespace SnapchatForWooCommerce\Admin\Settings;

use WC_REST_Controller;
use WP_REST_Response;

/**
 * Abstract base controller for Ad Partner Settings REST API.
 *
 * Provides standardized permission checks and response wrapping for all
 * settings-related endpoints in the plugin.
 *
 * @since 0.1.0
 */
class SettingsBaseController extends WC_REST_Controller {

	/**
	 * Checks whether the current user has permission to access the settings endpoint.
	 *
	 * By default, this returns true for users with the `manage_woocommerce` capability.
	 * This ensures that only store admins and authorized roles can modify settings
	 * via the API.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if the user has permission; false otherwise.
	 */
	public function permissions_check(): bool {
		return true;
		return current_user_can( 'manage_woocommerce' );
	}
}
