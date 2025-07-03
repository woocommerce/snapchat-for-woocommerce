<?php
/**
 * REST controller for managing the Snapchat onboarding state.
 *
 * This controller handles the retrieval and update of onboarding status
 * and step for the authenticated merchant, allowing the plugin to track
 * the merchant's progress through the setup flow.
 *
 * Onboarding data is stored locally in WordPress options.
 *
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use WP_REST_Request;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the `/onboarding/setup` endpoint.
 *
 * @since 0.1.0
 */
class OnboardingController extends SettingsBaseController {

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = 'wc/sfw/snapchat';
	}

	/**
	 * Registers REST API routes.
	 *
	 * Provides GET and POST methods for onboarding state:
	 * - GET  /wp-json/wc/sfw/snapchat/setup
	 * - POST /wp-json/wc/sfw/snapchat/setup
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/setup',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'permissions_check' ),
					'callback'            => array( $this, 'get_setup_state' ),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this, 'permissions_check' ),
					'callback'            => array( $this, 'set_setup_state' ),
				),
				'schema' => array( $this, 'setup_state_schema' ),
			)
		);
	}

	/**
	 * Returns the current onboarding setup state.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_setup_state(): WP_REST_Response {
		return rest_ensure_response(
			array(
				'status' => Options::get( OptionDefaults::ONBOARDING_STATUS ),
				'step'   => Options::get( OptionDefaults::ONBOARDING_STEP ),
			)
		);
	}

	/**
	 * Sets the onboarding setup state.
	 *
	 * Accepts `status` and `step` fields in the request body and stores them
	 * in WordPress options. Fields are optional, and only those provided
	 * will be updated.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request The REST request containing onboarding state data.
	 * @return WP_REST_Response
	 */
	public function set_setup_state( WP_REST_Request $request ): WP_REST_Response {
		$status = $request['status'] ?? '';
		$step   = $request['step'] ?? '';

		if ( $status ) {
			Options::set( OptionDefaults::ONBOARDING_STATUS, $status );
		}

		if ( $step ) {
			Options::set( OptionDefaults::ONBOARDING_STEP, $step );
		}

		return rest_ensure_response(
			array(
				'status' => Options::get( OptionDefaults::ONBOARDING_STATUS ),
				'step'   => Options::get( OptionDefaults::ONBOARDING_STEP ),
			)
		);
	}

	/**
	 * Returns the JSON schema for the `/onboarding/setup` endpoint.
	 *
	 * This schema defines the expected structure of the onboarding state,
	 * including the `status` and `step` fields, both of which are strings.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for onboarding setup state.
	 */
	public function setup_state_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'snapchat_setup_state',
			'type'       => 'object',
			'properties' => array(
				'status' => array(
					'description' => 'The status of merchant onboarding.',
					'type'        => 'string',
				),
				'step'   => array(
					'description' => 'The current step of merchant onboarding process.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'status', 'step' ),
		);
	}
}
