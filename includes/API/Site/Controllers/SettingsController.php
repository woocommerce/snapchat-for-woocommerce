<?php
/**
 * REST controller for managing Site settings.
 *
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the `/settings` endpoint.
 *
 * @since 0.1.0
 */
class SettingsController extends RESTBaseController {
	/**
	 * Registers REST API routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * GET /settings
		 * - Returns site settings.
		 *
		 * POST /settings
		 * - Set site settings.
		 */
		register_rest_route(
			Config::REST_NAMESPACE . '/snapchat',
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'set_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Returns the Site settings.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		return rest_ensure_response(
			array(
				'capi_enabled' => 'yes' === Options::get( OptionDefaults::CONVERSIONS_ENABLED ),
			)
		);
	}

	/**
	 * Sets the Site settings.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function set_settings( $request ) {
		$capi_token = null;

		if ( isset( $request['capi_enabled'] ) ) {
			$capi_token = rest_sanitize_boolean( $request['capi_enabled'] );
		}

		if ( ! is_null( $capi_token ) ) {
			Options::set( OptionDefaults::CONVERSIONS_ENABLED, $capi_token ? 'yes' : 'no' );
		}

		return rest_ensure_response(
			array(
				'capi_enabled' => 'yes' === Options::get( OptionDefaults::CONVERSIONS_ENABLED ),
			)
		);
	}
}
