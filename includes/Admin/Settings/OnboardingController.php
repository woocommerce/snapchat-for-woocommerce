<?php

namespace SnapchatForWooCommerce\Admin\Settings;

use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

class OnboardingController extends SettingsBaseController {
	public function __construct() {
		$this->namespace = 'wc/sfw/snapchat';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/onboarding/setup',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'permissions_check' ),
					'callback'            => array( $this, 'get_setup_state' )
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this, 'permissions_check' ),
					'callback'            => array( $this, 'set_setup_state' )
				),
				'schema' => array( $this, 'setup_state_schema' )
			)
		);
	}

	public function get_setup_state() {
		return rest_ensure_response( array(
			'status' => Options::get( OptionDefaults::ONBOARDING_STATUS ),
			'step'   => Options::get( OptionDefaults::ONBOARDING_STEP ),
		) );
	}

	public function set_setup_state( $request ) {
		$status = $request['status'] ?? '';
		$step   = $request['step'] ?? '';

		if ( $status ) {
			Options::set( OptionDefaults::ONBOARDING_STATUS, $status );
		}

		if ( $step ) {
			Options::set( OptionDefaults::ONBOARDING_STEP, $step );
		}

		return rest_ensure_response( array(
			'status' => Options::get( OptionDefaults::ONBOARDING_STATUS ),
			'step'   => Options::get( OptionDefaults::ONBOARDING_STEP ),
		) );
	}

	public function setup_state_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'snapchat_setup_state',
			'type'       => 'object',
			'properties' => array(
				'status' => array(
					'description' => 'The status of merchant onboarding.',
					'type'        => 'string',
				),
				'step' => array(
					'description' => 'The current step of merchant onboarding process.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'status', 'step' ),
		);
	}
}
