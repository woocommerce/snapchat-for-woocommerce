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
use WP_REST_Response;

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

	/**
	 * Retrieves a single catalog by ID.
	 *
	 * Used to verify that a locally-stored catalog ID still corresponds to an
	 * existing catalog on the Ad Partner side before deciding whether to create
	 * a new one.
	 *
	 * @since 1.0.3
	 *
	 * @param string $catalog_id Catalog ID to look up.
	 *
	 * @return \WP_REST_Response|WP_Error REST response from WCS, or error on invalid
	 *                                    input or remote failure (including HTTP 404).
	 */
	public function get_catalog( string $catalog_id ) {
		if ( '' === $catalog_id ) {
			return new WP_Error(
				'catalog_id_empty',
				__( 'Catalog ID is required.', 'snapchat-for-woocommerce' ),
			);
		}

		return $this->wcs->proxy_get(
			'/ads/v1/catalogs/' . rawurlencode( $catalog_id )
		);
	}

	/**
	 * Returns an existing catalog when the stored catalog ID is still valid on
	 * the Ad Partner side AND matches the currently-connected organization and
	 * pixel, otherwise creates a new one.
	 *
	 * Prevents duplicate catalog creation on disconnect + reconnect cycles by
	 * verifying the previously stored `CATALOG_ID` option against the Ad
	 * Partner before falling back to {@see self::create()}.
	 *
	 * Reuse is rejected (and a fresh catalog is created) in any of these cases:
	 *  - no `CATALOG_ID` is stored (first-time connect);
	 *  - `get_catalog()` returns a WP_Error (e.g. HTTP 404 for a deleted catalog);
	 *  - the GET response is 2xx but the body is not shaped as Snap's catalog
	 *    envelope (malformed upstream);
	 *  - the stored catalog belongs to a different organization than the one
	 *    currently in `ORGANIZATION_ID` (user reconnected against a different
	 *    Snapchat org);
	 *  - the stored catalog is not attached to the current `PIXEL_ID` (user
	 *    reconnected with a different pixel selected).
	 *
	 * Snap's `GET /v1/catalogs/{id}` returns the same "bulk" envelope as its
	 * `POST /v1/organizations/{org_id}/catalogs`, so the response from
	 * {@see self::get_catalog()} can be passed straight back to the caller
	 * without re-wrapping: `{ request_status, request_id, catalogs: [ {
	 * sub_request_status, catalog: {...} } ] }`.
	 *
	 * Every rejection branch emits a warning to the WooCommerce log (source
	 * `snapchat-for-woocommerce`, gated on `SNAPCHAT_FOR_WOOCOMMERCE_DEBUG`)
	 * explaining why the stored catalog was discarded — useful for diagnosing
	 * "a new catalog was created after reconnect" support reports.
	 *
	 * @since 1.0.3
	 *
	 * @return \WP_REST_Response|WP_Error REST response wrapping the catalog,
	 *                                    or a WP_Error if create() fails.
	 */
	public function find_or_create() {
		$stored_id = Options::get( OptionDefaults::CATALOG_ID );

		if ( empty( $stored_id ) ) {
			return $this->create();
		}

		$existing = $this->get_catalog( $stored_id );

		if ( is_wp_error( $existing ) ) {
			$this->log_stale_catalog(
				$stored_id,
				sprintf(
					'get_catalog returned WP_Error (code=%1$s, message=%2$s)',
					$existing->get_error_code(),
					$existing->get_error_message()
				)
			);
			return $this->create();
		}

		$data    = $existing->get_data();
		$catalog = ( isset( $data['catalogs'][0]['catalog'] ) && is_array( $data['catalogs'][0]['catalog'] ) )
			? $data['catalogs'][0]['catalog']
			: null;

		if ( null === $catalog ) {
			$this->log_stale_catalog( $stored_id, 'GET /catalogs/{id} returned a 2xx without a catalogs[0].catalog object' );
			return $this->create();
		}

		$org_id        = Options::get( OptionDefaults::ORGANIZATION_ID );
		$pixel_id      = Options::get( OptionDefaults::PIXEL_ID );
		$remote_org_id = isset( $catalog['organization_id'] ) ? (string) $catalog['organization_id'] : '';

		if ( empty( $org_id ) || '' === $remote_org_id || $remote_org_id !== $org_id ) {
			$this->log_stale_catalog(
				$stored_id,
				sprintf(
					'organization mismatch (local=%1$s, remote=%2$s)',
					self::format( $org_id ),
					self::format( $remote_org_id )
				)
			);
			return $this->create();
		}

		$event_sources    = ( isset( $catalog['event_sources'] ) && is_array( $catalog['event_sources'] ) ) ? $catalog['event_sources'] : array();
		$event_source_ids = array_column( $event_sources, 'id' );

		if ( empty( $pixel_id ) || ! in_array( $pixel_id, $event_source_ids, true ) ) {
			$this->log_stale_catalog(
				$stored_id,
				sprintf(
					'pixel not attached to stored catalog (local_pixel=%1$s, remote_event_source_ids=%2$s)',
					self::format( $pixel_id ),
					wp_json_encode( $event_source_ids )
				)
			);
			return $this->create();
		}

		return $existing;
	}

	/**
	 * Logs a warning that the stored `CATALOG_ID` is unusable and a new catalog
	 * will be created in its place. Gated on the plugin's debug flag so it is a
	 * no-op in normal production use.
	 *
	 * @since 1.0.3
	 *
	 * @param string $catalog_id The stored catalog ID that was rejected.
	 * @param string $reason     Human-readable reason for rejection.
	 *
	 * @return void
	 */
	private function log_stale_catalog( string $catalog_id, string $reason ): void {
		if ( ! Helper::is_logging_enabled() ) {
			return;
		}

		$logger = wc_get_logger();
		$logger->warning(
			sprintf(
				'Creating new Snapchat catalog (stored id "%1$s" unusable): %2$s',
				$catalog_id,
				$reason
			),
			array( 'source' => 'snapchat-for-woocommerce' )
		);
	}

	/**
	 * Renders a value safely for log output. Empty strings, nulls and booleans
	 * are stringified explicitly so log entries read "local=(empty)" rather
	 * than the ambiguous "local=".
	 *
	 * @since 1.0.3
	 *
	 * @param mixed $value Value to render.
	 *
	 * @return string
	 */
	private static function format( $value ): string {
		if ( '' === $value || null === $value || false === $value ) {
			return '(empty)';
		}

		return (string) $value;
	}
}
