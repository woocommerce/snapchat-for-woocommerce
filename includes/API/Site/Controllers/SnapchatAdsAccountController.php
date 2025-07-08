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
		 * - Returns an array of OptionDefaults::AD_ACCOUNT_ID
		 *   and OptionDefaults::AD_ACCOUNT_NAME
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
		$ad_account_id   = Options::get( OptionDefaults::AD_ACCOUNT_ID );
		$ad_account_name = Options::get( OptionDefaults::AD_ACCOUNT_NAME );

		return rest_ensure_response(
			array(
				'id'   => $ad_account_id,
				'name' => $ad_account_name,
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
