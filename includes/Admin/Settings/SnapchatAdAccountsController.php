<?php
/**
 * REST controller for managing Ad Partner ad accounts.
 *
 * This controller handles listing, selecting, and retrieving
 * the selected ad account associated with the merchant's selected
 * Snapchat organization.
 *
 * @package SnapchatForWooCommerce\Admin\Settings
 */

namespace SnapchatForWooCommerce\Admin\Settings;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the Ads Accounts endpoint.
 *
 * @since 0.1.0
 */
class SnapchatAdAccountsController extends SettingsBaseController {

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = 'wc/sfw/snapchat';
	}

	/**
	 * Registers REST API routes for ad accounts.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * GET /wp-json/wc/sfw/snapchat/ads_accounts?org_id=abc123
		 * - Returns filtered value from OptionDefaults::ORGANIZATIONS
		 *
		 * DELETE /wp-json/wc/sfw/snapchat/ads_accounts
		 * - Deletes OptionDefaults::AD_ACCOUNT_ID.
		 */
		register_rest_route(
			$this->namespace,
			'/ads_accounts',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_ads_accounts' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'org_id' => array(
							'description' => 'The organization ID to fetch Ad accounts from.',
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'ads_accounts_schema' ),
			)
		);

		/**
		 * GET /wp-json/wc/sfw/snapchat/ads_account
		 * - Returns value from OptionDefaults::AD_ACCOUNT_ID
		 *
		 * POST /wp-json/wc/sfw/snapchat/ads_account
		 * - params: { "id": "abc123" }
		 * - Saves value to OptionDefaults::AD_ACCOUNT_ID
		 */
		register_rest_route(
			$this->namespace,
			'/ads_account',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_ads_account_id' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_ads_account_id' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => 'The ad account ID to store.',
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_ads_account' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'ads_account_schema' ),
			)
		);
	}

	/**
	 * Returns all ad accounts for a given organization ID.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_ads_accounts( $request ) {
		$org_id = sanitize_text_field( $request->get_param( 'org_id' ) );
		$orgs   = Options::get( OptionDefaults::ORGANIZATIONS );

		if ( empty( $orgs ) ) {
			return new WP_Error(
				'no_organizations_cached',
				'Organization data not available. Please reload organizations first.',
				array( 'status' => 400 )
			);
		}

		$org = current(
			array_filter(
				$orgs,
				fn( $entry ) => $entry['id'] === $org_id
			)
		);

		if ( empty( $org ) || empty( $org['ad_accounts'] ) ) {
			return rest_ensure_response( array() );
		}

		return rest_ensure_response( array_values( $org['ad_accounts'] ) );
	}

	/**
	 * Stores the selected ad account ID.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function save_ads_account_id( $request ) {
		$ad_account_id = sanitize_text_field( $request['id'] );
		$orgs          = Options::get( OptionDefaults::ORGANIZATIONS );
		$org_id        = Options::get( OptionDefaults::ORGANIZATION_ID );

		if ( empty( $org_id ) ) {
			return new WP_Error(
				'org_id_not_set',
				'Set the organization ID first.'
			);
		}

		$org = current(
			array_filter(
				$orgs,
				fn( $org ) => $org['id'] === $org_id
			)
		);

		if ( empty( $org ) && ! is_array( $org ) ) {
			return rest_ensure_response( array( 'id' => '' ) );
		}

		$ad_accounts = current(
			array_filter(
				$org['ad_accounts'],
				fn( $account ) => $account['id'] === $ad_account_id
			)
		);

		if ( empty( $ad_accounts ) ) {
			return new WP_Error(
				'ads_account_not_found',
				'No details are associated with the provided account ID.'
			);
		}

		Options::set( OptionDefaults::AD_ACCOUNT_ID, $ad_account_id );

		return rest_ensure_response( array( 'id' => $ad_account_id ) );
	}

	/**
	 * Deletes all stored organizations and selection metadata.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function delete_ads_account() {
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Returns the selected ad account.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_ads_account_id() {
		return rest_ensure_response( array( 'id' => Options::get( OptionDefaults::AD_ACCOUNT_ID ) ) );
	}

	/**
	 * Returns the schema for the `/ads_accounts` REST route.
	 *
	 * This schema describes an array of ad account objects, each containing
	 * an `id` and a `name`. It is used for response validation and documentation
	 * purposes by the REST API.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for the ad accounts.
	 */
	public function ads_accounts_schema(): array {
		return array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'snapchat_ads_accounts',
			'type'    => 'array',
			'items'   => array(
				'type'       => 'object',
				'properties' => array(
					'id'   => array(
						'description' => 'The unique ID of the Snapchat Ads Account.',
						'type'        => 'string',
					),
					'name' => array(
						'description' => 'The name of the Ads Account.',
						'type'        => 'string',
					),
				),
				'required'   => array( 'id', 'name' ),
			),
		);
	}

	/**
	 * Returns the schema for the `/ads_account` REST route.
	 *
	 * This schema describes a single ad account object with a required `id` property.
	 * It is used to validate POST request bodies and document the API structure.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for the selected ad account.
	 */
	public function ads_account_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'snapchat_ads_account',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => 'The unique ID of the selected Snapchat Ads Account.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'id' ),
		);
	}
}
