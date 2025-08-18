<?php
/**
 * REST controller for managing the Snapchat Ads OAuth connection.
 *
 * This controller handles the Jetpack-authenticated connection flow with
 * WooCommerce Connect Server (WCS) for Snapchat Ads.
 *
 * It supports three endpoints:
 * - POST   /connect     – Start the Snapchat OAuth flow.
 * - GET    /connection  – Check current Snapchat connection status.
 * - DELETE /connection  – Disconnect the Snapchat account.
 *
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use WP_Error;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\API\AdPartner\AdPartnerApi;

/**
 * Controller for setting up and managing the Snapchat account connection.
 *
 * @since 0.1.0
 */
class SnapchatBusinessExtensionController extends RESTBaseController {

	/**
	 * WCS proxy request client.
	 *
	 * @var WcsClient
	 */
	protected WcsClient $wcs;

	/**
	 * Instance of the Ad Partner API.
	 *
	 * @var AdPartnerApi
	 */
	protected AdPartnerApi $ad_partner_api;

	/**
	 * Constructor.
	 *
	 * @param WcsClient    $wcs            WCS proxy request client.
	 * @param AdPartnerApi $ad_partner_api Ad Partner API.
	 */
	public function __construct( WcsClient $wcs, AdPartnerApi $ad_partner_api ) {
		$this->wcs            = $wcs;
		$this->ad_partner_api = $ad_partner_api;
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			Config::REST_NAMESPACE . '/snapchat',
			'connect',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'initiate_oauth' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/snapchat',
			'connection',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'check_connection' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_connection' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			),
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/snapchat',
			'config',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'set_config' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id'             => array(
							'description' => 'The config_id returned by Snapchat.',
							'type'        => 'string',
							'required'    => true,
						),
						'products_token' => array(
							'description' => 'The products_token returned by WCS.',
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'config_schema' ),
			),
		);
	}

	/**
	 * Sends a GET request to the WCS `/connection/status` endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Connection status or error.
	 */
	public function get_connection_status() {
		return $this->wcs->proxy_get( 'connection/status' );
	}

	/**
	 * Sends a POST request to the WCS `/connection/connect` endpoint to initiate a connection.
	 *
	 * @since 0.1.0
	 *
	 * @param string $return_url    URL to redirect the user to after authorization.
	 *
	 * @return WP_REST_Response|WP_Error Connection initiation response or error.
	 */
	public function start_connection( string $return_url ) {
		return $this->wcs->proxy_get( 'connection/connect', array( 'return_url' => $return_url ) );
	}

	/**
	 * Sends a GET request to the WCS `/connection` endpoint to disconnect the Ad Partner.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Disconnection response or error.
	 */
	public function stop_connection() {
		return $this->wcs->proxy_get( 'connection/disconnect' );
	}

	/**
	 * Saves the selected Snapchat Business Extension configuration.
	 *
	 * Fetches the configuration details using the provided config ID and stores
	 * the related Organization ID, Ad Account ID, Pixel ID, and Conversion API token.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_config( $request ) {
		$config_id      = sanitize_text_field( $request['id'] );
		$products_token = sanitize_text_field( $request['products_token'] );

		if ( empty( $config_id ) ) {
			return rest_ensure_response( ( array( 'id' => '' ) ) );
		}

		Options::set( OptionDefaults::CONFIG_ID, $config_id );

		if ( empty( $products_token ) ) {
			if ( Helper::is_logging_enabled() ) {
				$logger = wc_get_logger();
				$logger->warning(
					'products_token not set. Auto product feed creation will fail.'
				);
			}
		} else {
			Options::set( OptionDefaults::WCS_PRODUCTS_TOKEN, $products_token );
		}

		$response = $this->wcs->proxy_get( '/ads/v1/business_extension_configurations/' . $config_id );

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
				),
				500
			);
		}

		$data        = $response->get_data();
		$client_data = $data['business_extension_configuration'];

		if ( ! empty( $client_data['organization_id'] ) ) {
			Options::set( OptionDefaults::ORGANIZATION_ID, $client_data['organization_id'] );
		}

		if ( ! empty( $client_data['organization_name'] ) ) {
			Options::set( OptionDefaults::ORGANIZATION_NAME, $client_data['organization_name'] );
		}

		if ( ! empty( $client_data['ad_account_id'] ) ) {
			Options::set( OptionDefaults::AD_ACCOUNT_ID, $client_data['ad_account_id'] );
		}

		if ( ! empty( $client_data['ad_account_name'] ) ) {
			Options::set( OptionDefaults::AD_ACCOUNT_NAME, $client_data['ad_account_name'] );
		}

		if ( ! empty( $client_data['pixel_id'] ) ) {
			Options::set( OptionDefaults::PIXEL_ID, $client_data['pixel_id'] );
		}

		if ( ! empty( $client_data['capi_token'] ) ) {
			Options::set( OptionDefaults::CONVERSION_ACCESS_TOKEN, $client_data['capi_token'] );
		}

		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );

		$response = $this->ad_partner_api->catalog->create();

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
				),
				500
			);
		}

		$data         = $response->get_data();
		$catalog_data = $data['catalogs'];

		if ( ! empty( $catalog_data ) && ! empty( $catalog_data[0] ) ) {
			$catalog = $catalog_data[0]['catalog'];

			Options::set( OptionDefaults::CATALOG_ID, $catalog['id'] );
		}

		/**
		 * Triggers when the Snapchat onboarding process is completed.
		 *
		 * @since 0.1.0
		 */
		do_action( Helper::with_prefix( 'onboarding_complete' ) );

		return rest_ensure_response(
			array(
				'org_id'      => Options::get( OptionDefaults::ORGANIZATION_ID ),
				'org_name'    => Options::get( OptionDefaults::ORGANIZATION_NAME ),
				'ad_acc_id'   => Options::get( OptionDefaults::AD_ACCOUNT_ID ),
				'ad_acc_name' => Options::get( OptionDefaults::AD_ACCOUNT_NAME ),
				'pixel_id'    => Options::get( OptionDefaults::PIXEL_ID ),
				'catalog_id'  => Options::get( OptionDefaults::CATALOG_ID ),
			)
		);
	}

	/**
	 * Starts the OAuth flow.
	 *
	 * @return WP_REST_Response
	 */
	public function initiate_oauth() {
		$return_url = rawurlencode( admin_url( 'admin.php?page=wc-admin&path=/snapchat/setup' ) );
		$response   = $this->start_connection( $return_url );

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

		if ( empty( $data['oauth_url'] ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => __( 'Invalid response from WCS. OAuth URL missing.', 'snapchat-for-woocommerce' ),
				)
			);
		}

		return rest_ensure_response(
			array( 'url' => esc_url_raw( $data['oauth_url'] ) )
		);
	}

	/**
	 * Checks connection status.
	 *
	 * @return WP_REST_Response
	 */
	public function check_connection() {
		// Bail early if Jetpack is disconnected.
		if ( 'yes' !== Options::get( OptionDefaults::IS_JETPACK_CONNECTED ) ) {
			return rest_ensure_response(
				array(
					'status' => 'disconnected',
				)
			);
		}

		$response = $this->get_connection_status();

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
				),
				500
			);
		}

		$data = $response->get_data();

		return rest_ensure_response(
			array(
				'status' => $data['status'],
			)
		);
	}

	/**
	 * Disconnects the merchant account.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_connection() {
		$config_id = Options::get( OptionDefaults::CONFIG_ID );

		if ( $config_id ) {
			$response = $this->wcs->proxy_delete(
				'/ads/v1/business_extension_configurations/' . $config_id
			);

			if ( is_wp_error( $response ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => $response->get_error_message(),
						'data'    => $response->get_error_data(),
					),
					500
				);
			}

			$data = $response->get_data();

			if ( is_wp_error( $data ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => $response->get_error_message(),
						'data'    => $response->get_error_data(),
					),
					500
				);
			}

			if ( ! empty( $data['request_status'] ) && 'SUCCESS' === $data['request_status'] ) {
				$response = $this->stop_connection();

				if ( is_wp_error( $response ) ) {
					return new WP_REST_Response(
						array(
							'status'  => 'error',
							'message' => $response->get_error_message(),
							'data'    => $response->get_error_data(),
						),
						500
					);
				}

				$data         = $response->get_data();
				$oauth_status = '';

				if ( $data['status'] && 'disconnected' === $data['status'] ) {
					$oauth_status = $data['status'];
				}

				Options::delete( OptionDefaults::CONFIG_ID );
				Options::delete( OptionDefaults::ORGANIZATION_ID );
				Options::delete( OptionDefaults::ORGANIZATION_NAME );
				Options::delete( OptionDefaults::AD_ACCOUNT_ID );
				Options::delete( OptionDefaults::AD_ACCOUNT_NAME );
				Options::delete( OptionDefaults::CONVERSION_ACCESS_TOKEN );
				Options::delete( OptionDefaults::PIXEL_ID );
				Options::delete( OptionDefaults::IS_JETPACK_CONNECTED );
				Options::delete( OptionDefaults::ONBOARDING_STATUS );
				Options::delete( OptionDefaults::LAST_EXPORT_TIMESTAMP );
				Options::delete( OptionDefaults::EXPORT_FILE_PATH );
				Options::delete( OptionDefaults::EXPORT_FILE_URL );
				Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );
				Options::delete( OptionDefaults::FEED_STATUS );
				Transients::delete( TransientDefaults::PIXEL_SCRIPT );

				/**
				 * Triggers when Snapchat is disconnected.
				 *
				 * @since 0.1.0
				 */
				do_action( Helper::with_prefix( 'snapchat_disconnected' ) );

				return rest_ensure_response(
					array(
						'status' => $oauth_status,
					)
				);
			}
		}

		return new WP_REST_Response(
			array(
				'status'  => 'error',
				'message' => 'Config id missing',
			),
			500
		);
	}

	/**
	 * Returns the JSON Schema for the `/config` endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema definition.
	 */
	public function config_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'snapchat_merchant_config',
			'type'       => 'object',
			'properties' => array(
				'org_id'      => array(
					'description' => "Selected Organization's id.",
					'type'        => 'string',
				),
				'org_name'    => array(
					'description' => "Selected Organization's name.",
					'type'        => 'string',
				),
				'ad_acc_id'   => array(
					'description' => 'Selected Ad Account id.',
					'type'        => 'string',
				),
				'ad_acc_name' => array(
					'description' => 'Selected Ad Account name.',
					'type'        => 'string',
				),
				'pixel_id'    => array(
					'description' => 'Selected Pixel id.',
					'type'        => 'string',
				),
				'catalog_id'  => array(
					'description' => 'Created Catalog id.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'id' ),
		);
	}
}
