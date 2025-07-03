<?php
/**
 * Trait for handling admin-level permission checks for REST API endpoints.
 *
 * This trait provides a reusable method for verifying whether the current user
 * has the appropriate capabilities to access or modify administrative settings
 * via the plugin’s REST API.
 *
 * Primarily used in controller classes to enforce role-based access control.
 * Designed to be included in REST controllers where administrative permission checks
 * are required consistently.
 *
 * @package SnapchatForWooCommerce\API
 */

namespace SnapchatForWooCommerce\API;

/**
 * Provides permission-checking logic for admin-restricted REST endpoints.
 *
 * Controllers using this trait can perform consistent capability checks by
 * invoking the `permissions_check()` method, ensuring that only users
 * with the `manage_woocommerce` capability can proceed.
 *
 * @since 0.1.0
 */
trait AdminPermissionsTrait {
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
		return current_user_can( 'manage_woocommerce' );
	}
}
