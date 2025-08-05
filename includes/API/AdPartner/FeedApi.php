<?php
/**
 * API module for managing product feeds.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Feed API via WooCommerce Connect Server (WCS). It supports feed-related
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
use SnapchatForWooCommerce\Admin\Export\Writer\CsvExportWriter;
use WP_Error;

/**
 * API module for managing product feeds.
 *
 * This class provides the ability to create a new product feed within an
 * existing catalog under the merchant's Ad Partner account.
 *
 * @since 0.1.0
 */
class FeedApi extends BaseAdPartnerApi {

	/**
	 * Creates a product feed for the current catalog.
	 *
	 * This method submits the feed creation request using the
	 * catalog ID configured in the plugin options.
	 *
	 * It returns a {@see WP_REST_Response} on success or a {@see WP_Error}
	 * on failure, depending on whether the required prerequisites are met.
	 *
	 * Requirements:
	 * - Catalog ID must be saved in plugin options.
	 * - Feed URL must be available via {@see OptionDefaults::EXPORT_FILE_URL}.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if inputs are missing.
	 */
	public function create() {
		$catalog_id = Options::get( OptionDefaults::CATALOG_ID );

		if ( ! $catalog_id ) {
			return new WP_Error(
				'catalog_id_not_set',
				__( 'Catalog ID not found.', 'snapchat-for-woocommerce' ),
			);
		}

		$payload = array(
			'product_feeds' => array(
				array(
					'catalog_id'       => $catalog_id,
					'name'             => Helper::get_store_name( 'feed' ),
					'default_currency' => get_woocommerce_currency(),
					'status'           => 'ACTIVE',
					'vertical'         => 'COMMERCE',
					'schedule'         => array(
						'interval_type' => 'DAILY',
						'timezone'      => get_option( 'timezone_string' ),
						'url'           => sprintf(
							'%1$s/%2$s/products-%3$s.csv',
							$this->wcs->get_wcs_url(),
							CsvExportWriter::EXPORT_FOLDER,
							Options::get( OptionDefaults::WCS_PRODUCTS_TOKEN )
						),
					),
				),
			),
		);

		$response = $this->wcs->proxy_post(
			'/ads/v1/catalogs/' . $catalog_id . '/product_feeds',
			$payload
		);

		return $response;
	}
}
