<?php
/**
 * API module for managing product catalogs.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Catalog API via WooCommerce Connect Server (WCS). It supports catalog-related
 * operations such as creation, and can be extended to include retrieval,
 * deletion, or updates in the future.
 *
 * Requests are constructed using merchant-specific identifiers and sent to
 * the WCS proxy endpoint, which handles secure authentication and communication
 * with the Ad Partner's remote API.
 *
 * @since 0.1.0
 * @package SnapchatForWooCommerce\API\AdPartner
 */

namespace SnapchatForWooCommerce\API\AdPartner;

use SnapchatForWooCommerce\API\AdPartner\BaseAdPartnerApi;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Helper;
use WP_Error;

/**
 * API module for managing product catalogs.
 *
 * This class provides the ability to create a new catalog under the merchant's
 * Ad Partner account, associating it with an existing Pixel.
 *
 * @since 0.1.0
 */
class CatalogApi extends BaseAdPartnerApi {

	/**
	 * Creates a product catalog for the current merchant organization.
	 *
	 * This method builds and submits the catalog creation request using the
	 * organization ID and Pixel ID configured in the plugin settings.
	 *
	 * It returns a {@see WP_REST_Response} on success or a {@see WP_Error}
	 * on failure, depending on whether the required prerequisites are met.
	 *
	 * Requirements:
	 * - Organization ID must be saved in plugin options.
	 * - Pixel ID must be saved in plugin options.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if inputs are missing.
	 */
	public function create() {
		$org_id = Options::get( OptionDefaults::ORGANIZATION_ID );

		if ( ! $org_id ) {
			return new WP_Error(
				'org_id_not_set',
				__( 'Organization ID not found.', 'snapchat-for-woocommerce' ),
			);
		}

		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		if ( ! $pixel_id ) {
			return new WP_Error(
				'pixel_id_not_set',
				__( 'Pixel ID not found.', 'snapchat-for-woocommerce' ),
			);
		}

		$payload = array(
			'catalogs' => array(
				array(
					'organization_id' => $org_id,
					'name'            => Helper::get_store_name( 'catalog' ),
					'vertical'        => 'COMMERCE',
					'event_sources'   => array(
						array(
							'id'   => $pixel_id,
							'type' => 'PIXEL',
						),
					),
				),
			),
		);

		$response = $this->wcs->proxy_post(
			'/ads/v1/organizations/' . $org_id . '/catalogs',
			$payload
		);

		return $response;
	}
}
