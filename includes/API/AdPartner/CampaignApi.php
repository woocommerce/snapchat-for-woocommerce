<?php
/**
 * API module for ad campaign lookups.
 *
 * Wraps the Ad Partner's campaign endpoints, sent through the WooCommerce
 * Connect Server (WCS) proxy.
 *
 * @since n.e.x.t
 * @package SnapchatForWooCommerce\API\AdPartner
 */

namespace SnapchatForWooCommerce\API\AdPartner;

use SnapchatForWooCommerce\API\AdPartner\BaseAdPartnerApi;
use WP_Error;

/**
 * API module for ad campaign lookups.
 *
 * @since n.e.x.t
 */
class CampaignApi extends BaseAdPartnerApi {

	/**
	 * Retrieves the campaigns for an ad account.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $ad_account_id Ad account to list campaigns for.
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if the request failed.
	 */
	public function get_campaigns( string $ad_account_id ) {
		return $this->wcs->proxy_get(
			'/ads/v1/adaccounts/' . rawurlencode( $ad_account_id ) . '/campaigns'
		);
	}
}
