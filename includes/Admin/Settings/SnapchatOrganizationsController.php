<?php
/**
 * REST controller for managing Snapchat organizations.
 *
 * This controller allows retrieving, selecting, and getting the selected organization
 * associated with the authenticated merchant's Snapchat account.
 *
 * It fetches organization and ad account data from the Snapchat API
 * via the WooCommerce Connect Server (WCS) and stores relevant selections
 * in the local WordPress options.
 *
 * @package SnapchatForWooCommerce\Admin\Settings
 */

namespace SnapchatForWooCommerce\Admin\Settings;

use WP_REST_Response;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the `/organizations` endpoint.
 *
 * @since 0.1.0
 */
class SnapchatOrganizationsController extends SettingsBaseController {

	/**
	 * WCS proxy request client.
	 *
	 * @var WcsClient
	 */
	protected WcsClient $wcs;

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param WcsClient $wcs WCS proxy request client.
	 */
	public function __construct( WcsClient $wcs ) {
		$this->namespace = 'wc/sfw/snapchat';
		$this->wcs       = $wcs;
	}

	/**
	 * Registers REST API routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * GET /wp-json/wc/sfw/snapchat/organization
		 * - Returns an array of OptionDefaults::ORGANIZATION_ID
		 *   and OptionDefaults::ORGANIZATION_NAME
		 */
		register_rest_route(
			$this->namespace,
			'/organization',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_organization' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'organization_schema' ),
			)
		);
	}

	/**
	 * Returns the currently selected organization.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_organization() {
		$org_id   = Options::get( OptionDefaults::ORGANIZATION_ID );
		$org_name = Options::get( OptionDefaults::ORGANIZATION_NAME );

		if ( $org_id && $org_name ) {
			return rest_ensure_response(
				array(
					'id'   => $org_id,
					'name' => $org_name,
				)
			);
		}

		$response = $this->wcs->proxy_get(
			'/ads/v1/organizations/' . $org_id
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
				),
				500
			);
		}

		$data = $response->get_data();
		$orgs = $data['organizations'] ?? array();

		if ( empty( $orgs ) ) {
			return rest_ensure_response(
				array(
					'id'   => '',
					'name' => '',
				)
			);
		}

		$org_name = sanitize_text_field( $orgs[0]['organization']['name'] ?? '' );

		if ( $org_name ) {
			Options::set( OptionDefaults::ORGANIZATION_NAME, $org_name );
		}

		return rest_ensure_response(
			array(
				'id'   => $org_id,
				'name' => $org_name,
			)
		);
	}

	/**
	 * Returns the JSON schema for the `/organization` REST endpoint.
	 *
	 * This schema defines a single object with a required `id` property
	 * representing the selected Snapchat organization. It is used to validate
	 * incoming POST payloads and document the expected data format.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for a selected organization.
	 */
	public function organization_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'snapchat_organization',
			'type'       => 'object',
			'properties' => array(
				'id'   => array(
					'description' => 'The unique ID of the selected Snapchat Organization.',
					'type'        => 'string',
				),
				'name' => array(
					'description' => 'The name of the selected Snapchat Organization.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'id', 'name' ),
		);
	}
}
