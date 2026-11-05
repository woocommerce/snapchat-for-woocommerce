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
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Campaign\CampaignStatusService;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the `/organizations` endpoint.
 *
 * @since 0.1.0
 */
class SnapchatAccountController extends RESTBaseController {

	/**
	 * Campaign status used to report whether the ad account has a campaign.
	 *
	 * @since n.e.x.t
	 *
	 * @var CampaignStatusService
	 */
	protected CampaignStatusService $campaign_status;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param CampaignStatusService $campaign_status Campaign status service.
	 */
	public function __construct( CampaignStatusService $campaign_status ) {
		$this->campaign_status = $campaign_status;
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
		 * GET /account
		 * - Returns an array of merchant account details.
		 */
		register_rest_route(
			Config::REST_NAMESPACE . '/snapchat',
			'/account',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_account_details' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Returns an array of Merchant Account details.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_account_details() {
		$ad_account_id = (string) Options::get( OptionDefaults::AD_ACCOUNT_ID );

		return rest_ensure_response(
			array(
				'org_id'              => Options::get( OptionDefaults::ORGANIZATION_ID ),
				'org_name'            => Options::get( OptionDefaults::ORGANIZATION_NAME ),
				'ad_acc_id'           => $ad_account_id,
				'ad_acc_name'         => Options::get( OptionDefaults::AD_ACCOUNT_NAME ),
				'pixel_id'            => Options::get( OptionDefaults::PIXEL_ID ),
				'has_active_campaign' => '' === $ad_account_id ? false : $this->get_campaign_status(),
			)
		);
	}

	/**
	 * Returns whether the connected ad account has a campaign, checking live on a cache miss.
	 *
	 * This value is reported in the `sfw_onboarding_completed` tracking event
	 * to split completions with and without ads. The cache is cleared when
	 * onboarding completes, so falling back to the cache-only
	 * `has_campaigns()` (which assumes true until the background check runs)
	 * would report nearly every completion as "with ads". The live check only
	 * runs on a cache miss, and its result is cached for later requests.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool|null Whether the ad account has a campaign, or null if the
	 *                   live check failed.
	 */
	protected function get_campaign_status(): ?bool {
		$status = $this->campaign_status->get_cached_status();

		if ( null === $status ) {
			$this->campaign_status->refresh_cache();
			$status = $this->campaign_status->get_cached_status();
		}

		return $status;
	}

	/**
	 * Returns the JSON schema for the `/account` REST endpoint.
	 *
	 * This schema defines a single object with details about the merchant
	 * account.
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
				'org_id'              => array(
					'description' => 'Snapchat Organization id.',
					'type'        => 'string',
				),
				'org_name'            => array(
					'description' => 'Snapchat Organization name.',
					'type'        => 'string',
				),
				'ad_acc_id'           => array(
					'description' => 'Snapchat Account id.',
					'type'        => 'string',
				),
				'ad_acc_name'         => array(
					'description' => 'Snapchat Account name.',
					'type'        => 'string',
				),
				'pixel_id'            => array(
					'description' => 'Pixel id.',
					'type'        => 'string',
				),
				'has_active_campaign' => array(
					'description' => 'Whether the ad account has at least one campaign, or null if the check could not be completed.',
					'type'        => array( 'boolean', 'null' ),
				),
			),
			'required'   => array( 'org_id', 'org_name', 'ad_acc_id', 'ad_acc_name', 'pixel_id', 'has_active_campaign' ),
		);
	}
}
