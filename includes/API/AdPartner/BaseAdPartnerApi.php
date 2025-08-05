<?php
/**
 * Abstract base class for Ad Partner API modules.
 *
 * Provides a common foundation for all API module classes that interact with
 * the Ad Partner through WooCommerce Connect Server (WCS). This class stores
 * a reference to the shared {@see WcsClient} instance used for authenticated
 * HTTP requests to the WCS proxy endpoints.
 *
 * All specific API modules (e.g., CatalogApi, FeedApi) should extend this class
 * to gain access to the WCS client and promote consistent request handling.
 *
 * @package SnapchatForWooCommerce\API\AdPartner
 */

namespace SnapchatForWooCommerce\Api\AdPartner;

use SnapchatForWooCommerce\Connection\WcsClient;

/**
 * Base class for API modules that communicate with the Ad Partner via WCS.
 *
 * Encapsulates the shared WCS client and provides foundational setup
 * for subclasses handling specific API concerns (catalogs, feeds, etc).
 *
 * @since 0.1.0
 */
class BaseAdPartnerApi {
	/**
	 * WCS client used to proxy API requests to the Ad Partner.
	 *
	 * This instance provides authentication and routing logic for sending
	 * HTTP requests to the correct WooCommerce Connect Server endpoint.
	 *
	 * @since 0.1.0
	 * @var WcsClient
	 */
	protected WcsClient $wcs;

	/**
	 * Constructor.
	 *
	 * Accepts a {@see WcsClient} instance used for all outgoing API calls
	 * made by this module or its subclasses.
	 *
	 * @since 0.1.0
	 *
	 * @param WcsClient $wcs WCS client instance.
	 */
	public function __construct( WcsClient $wcs ) {
		$this->wcs = $wcs;
	}
}
