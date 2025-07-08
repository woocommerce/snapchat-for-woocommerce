<?php
/**
 * REST controller for managing Snapchat Ad Accounts.
 *
 * This controller allows retrieving, selecting, and getting the selected Ad account
 * associated with the authenticated merchant's Snapchat account.
 *
 * It fetches Ad account data from the Snapchat API
 * via the WooCommerce Connect Server (WCS) and stores relevant selections
 * in the local WordPress options.
 *
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the `/ads_account` endpoint.
 *
 * @since 0.1.0
 */
class SnapchatAdsAccountController extends RESTBaseController {

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
		$this->wcs = $wcs;
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
		 * GET /ads_account
		 * - Returns an array of OptionDefaults::ADS_ACCOUNT_ID
		 *   and OptionDefaults::ADS_ACCOUNT_NAME
		 */
		register_rest_route(
			Config::REST_NAMESPACE . '/snapchat',
			'/ads_account',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_ads_account' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'ads_account_schema' ),
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
	public function get_ads_account() {
		$ads_account_id   = Options::get( OptionDefaults::ADS_ACCOUNT_ID );
		$ads_account_name = Options::get( OptionDefaults::ADS_ACCOUNT_NAME );

		if ( $ads_account_id && $ads_account_name ) {
			return rest_ensure_response(
				array(
					'id'   => $ads_account_id,
					'name' => $ads_account_name,
				)
			);
		}

		$response = $this->wcs->proxy_get(
			'/ads/v1/adaccounts/' . $ads_account_id
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
		$orgs = $data['adaccounts'] ?? array();

		if ( empty( $orgs ) ) {
			return rest_ensure_response(
				array(
					'id'   => '',
					'name' => '',
				)
			);
		}

		$ads_account_name = sanitize_text_field( $orgs[0]['adaccount']['name'] ?? '' );

		if ( $ads_account_name ) {
			Options::set( OptionDefaults::ADS_ACCOUNT_NAME, $ads_account_name );
		}

		return rest_ensure_response(
			array(
				'id'   => $ads_account_id,
				'name' => $ads_account_name,
			)
		);
	}

	/**
	 * Returns the JSON schema for the `/ads_account` REST endpoint.
	 *
	 * This schema defines a single object with a required `id` property
	 * representing the selected Snapchat Ad Account. It is used to validate
	 * incoming POST payloads and document the expected data format.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for a selected Ad Account.
	 */
	public function ad_account_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'snapchat_ads_account',
			'type'       => 'object',
			'properties' => array(
				'id'   => array(
					'description' => 'The unique ID of the selected Snapchat Ad Account.',
					'type'        => 'string',
				),
				'name' => array(
					'description' => 'The name of the selected Snapchat Ad Account.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'id', 'name' ),
		);
	}
}
