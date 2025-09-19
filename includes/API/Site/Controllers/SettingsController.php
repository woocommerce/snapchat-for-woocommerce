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
use SnapchatForWooCommerce\Utils\Helper;

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
		$timestamp = Options::get( OptionDefaults::LAST_EXPORT_TIMESTAMP );

		return rest_ensure_response(
			$this->get_settings_response()
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
		$capi_token  = null;
		$collect_pii = null;

		if ( isset( $request['capi_enabled'] ) ) {
			$capi_token = rest_sanitize_boolean( $request['capi_enabled'] );
		}

		if ( isset( $request['collect_pii'] ) ) {
			$collect_pii = rest_sanitize_boolean( $request['collect_pii'] );
		}

		if ( ! is_null( $capi_token ) ) {
			Options::set( OptionDefaults::CONVERSIONS_ENABLED, $capi_token ? 'yes' : 'no' );
		}

		if ( ! is_null( $collect_pii ) ) {
			Options::set( OptionDefaults::COLLECT_PII, $collect_pii ? 'yes' : 'no' );
		}

		return rest_ensure_response(
			$this->get_settings_response()
		);
	}

	/**
	 * Returns the settings response structure.
	 *
	 * This method encapsulates the logic to build the response for the settings endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	private function get_settings_response() {
		$timestamp = Options::get( OptionDefaults::LAST_EXPORT_TIMESTAMP );
		$csv_path  = Options::get( OptionDefaults::EXPORT_FILE_PATH );

		return rest_ensure_response(
			array(
				'capi_enabled'          => 'yes' === Options::get( OptionDefaults::CONVERSIONS_ENABLED ),
				'collect_pii'           => 'yes' === Options::get( OptionDefaults::COLLECT_PII ),
				'trigger_export'        => ! file_exists( $csv_path ) && Helper::has_products() && (int) $timestamp <= ( time() - DAY_IN_SECONDS ),
				'last_export_timestamp' => Helper::get_formatted_timestamp( $timestamp ),
				'export_file_url'       => file_exists( $csv_path ) ? Options::get( OptionDefaults::EXPORT_FILE_URL ) : '',
			)
		);
	}
}
