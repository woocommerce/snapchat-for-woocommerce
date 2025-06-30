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

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
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
		$this->rest_base = 'organizations';
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
		 * GET /wp-json/wc/sfw/snapchat/organizations
		 * - Returns filtered value from OptionDefaults::ORGANIZATIONS
		 *
		 * DELETE /wp-json/wc/sfw/snapchat/organizations
		 * - Deletes OptionDefaults::ORGANIZATIONS
		 * - Deletes OptionDefaults::ORGANIZATION_ID
		 */
		register_rest_route(
			$this->namespace,
			'/organizations',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_organizations' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_organizations' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'organizations_schema' ),
			)
		);

		/**
		 * GET /wp-json/wc/sfw/snapchat/organization
		 * - Returns the value from OptionDefaults::ORGANIZATION_ID
		 *
		 * POST /wp-json/wc/sfw/snapchat/organization
		 * - Saves the value to OptionDefaults::ORGANIZATION_ID
		 * - payload: { id: 'org123' }
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
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'set_organization' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => 'The organization ID to connect.',
						),
					),
				),
				'schema' => array( $this, 'organization_schema' ),
			)
		);
	}

	/**
	 * Returns all available organizations.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_organizations( $request ) {
		$orgs = Options::get( OptionDefaults::ORGANIZATIONS );

		if ( empty( $orgs ) ) {
			$response = $this->wcs->proxy_get(
				'/ads/v1/me/organizations?with_ad_accounts=true',
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$orgs = $this->sanitize_orgs_response( $response->get_data() );
			Options::set( OptionDefaults::ORGANIZATIONS, $orgs );
		}

		$data = array_map(
			fn( $org ) => array(
				'id'   => $org['id'],
				'name' => $org['name'],
			),
			$orgs
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Stores the selected organization ID.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function set_organization( $request ) {
		$orgs   = Options::get( OptionDefaults::ORGANIZATIONS );
		$org_id = sanitize_text_field( $request['id'] );

		$org_exists = ! empty(
			array_filter(
				$orgs,
				fn( $org ) => $org['id'] === $org_id
			)
		);

		if ( ! $org_exists ) {
			return rest_ensure_response(
				array( 'id' => '' )
			);
		}

		Options::set( OptionDefaults::ORGANIZATION_ID, $org_id );

		return rest_ensure_response(
			array( 'id' => $org_id )
		);
	}

	/**
	 * Deletes all Options related to organizations.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function delete_organizations() {
		Options::delete( OptionDefaults::ORGANIZATIONS );
		Options::delete( OptionDefaults::ORGANIZATION_ID );

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Returns the currently selected organization.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_organization() {
		$orgs   = Options::get( OptionDefaults::ORGANIZATIONS );
		$org_id = Options::get( OptionDefaults::ORGANIZATION_ID );

		if ( empty( $orgs ) || empty( $org_id ) ) {
			return rest_ensure_response( array( 'id' => '' ) );
		}

		$selected = current(
			array_filter(
				$orgs,
				fn( $entry ) => (string) $entry['id'] === (string) $org_id
			)
		);

		if ( empty( $selected ) ) {
			return rest_ensure_response( array( 'id' => '' ) );
		}

		return rest_ensure_response(
			array( 'id' => $selected['id'] )
		);
	}

	/**
	 * Sanitizes the raw WCS response.
	 *
	 * @since 0.1.0
	 *
	 * @param array $data Raw API data from WCS.
	 * @return array Sanitized organizations.
	 */
	private function sanitize_orgs_response( array $data ): array {
		if ( empty( $data['organizations'] ) ) {
			return array();
		}

		return array_map(
			function ( $entry ) {
				$org = $entry['organization'];

				return array(
					'id'          => $org['id'],
					'name'        => $org['name'],
					'ad_accounts' => array_map(
						fn( $a ) => array(
							'id'   => $a['id'],
							'name' => $a['name'],
						),
						$org['ad_accounts'] ?? array()
					),
				);
			},
			$data['organizations']
		);
	}

	/**
	 * Returns the JSON schema for the `/organizations` REST endpoint.
	 *
	 * This schema defines an array of organization objects, each containing
	 * a required `id` and `name` property. It is used to document and validate
	 * the shape of the API response.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for a list of organizations.
	 */
	public function organizations_schema(): array {
		return array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'snapchat_organizations',
			'type'    => 'array',
			'items'   => array(
				'type'       => 'object',
				'properties' => array(
					'id'   => array(
						'description' => 'The unique ID of the Snapchat organization.',
						'type'        => 'string',
					),
					'name' => array(
						'description' => 'The name of the organization.',
						'type'        => 'string',
					),
				),
				'required'   => array( 'id', 'name' ),
			),
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
				'id' => array(
					'description' => 'The unique ID of the selected Snapchat Organization.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'id' ),
		);
	}
}
