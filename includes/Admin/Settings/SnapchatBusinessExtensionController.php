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
 * @package SnapchatForWooCommerce\Admin\Settings
 */

namespace SnapchatForWooCommerce\Admin\Settings;

use WP_REST_Response;
use WP_Error;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * Controller for setting up and managing the Snapchat account connection.
 *
 * @since 0.1.0
 */
class SnapchatBusinessExtensionController extends SettingsBaseController {

	/**
	 * WCS proxy request client.
	 *
	 * @var WcsClient
	 */
	protected WcsClient $wcs;

	/**
	 * Constructor.
	 *
	 * @param WcsClient $wcs WCS proxy request client.
	 */
	public function __construct( WcsClient $wcs ) {
		$this->wcs       = $wcs;
		$this->namespace = 'wc/sfw/snapchat';
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'connect',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'initiate_oauth' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
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
			$this->namespace,
			'config',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'set_config' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'The config_id returned by Snapchat.',
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
		return $this->wcs->proxy_get( 'connection/connect', array( 'returnUrl' => $return_url ) );
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
		$config_id = sanitize_text_field( $request['id'] );

		if ( empty( $config_id ) ) {
			return rest_ensure_response( ( array( 'id' => '' ) ) );
		}

		Options::set( OptionDefaults::CONFIG_ID, $config_id );

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

		if ( $client_data['organization_id'] ) {
			Options::set( OptionDefaults::ORGANIZATION_ID, $client_data['organization_id'] );
		}

		if ( $client_data['ad_account_id'] ) {
			Options::set( OptionDefaults::ADS_ACCOUNT_ID, $client_data['ad_account_id'] );
		}

		if ( $client_data['pixel_id'] ) {
			Options::set( OptionDefaults::PIXEL_ID, $client_data['pixel_id'] );
		}

		if ( $client_data['capi_token'] ) {
			Options::set( OptionDefaults::CONVERSION_ACCESS_TOKEN, $client_data['capi_token'] );
		}

		return rest_ensure_response( ( array( 'id' => $config_id ) ) );
	}

	/**
	 * Starts the OAuth flow.
	 *
	 * @return WP_REST_Response
	 */
	public function initiate_oauth() {
		$return_url = admin_url( 'admin.php?page=wc-admin&path=/snapchat/setup' );
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

		if ( empty( $data['oauthUrl'] ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => __( 'Invalid response from WCS. OAuth URL missing.', 'snapchat-for-woocommerce' ),
				)
			);
		}

		return rest_ensure_response(
			array( 'url' => esc_url_raw( $data['oauthUrl'] ) )
		);
	}

	/**
	 * Checks connection status.
	 *
	 * @return WP_REST_Response
	 */
	public function check_connection() {
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

		if ( $data['success'] && 'disconnected' === $data['success'] ) {
			$oauth_status = $data['success'];
		}

		$config_id = Options::get( OptionDefaults::CONFIG_ID );

		if ( $config_id ) {
			$response = $this->wcs->proxy_delete(
				'/ads/v1/business_extension_configurations/' . $config_id
			);

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
		}

		Options::delete( OptionDefaults::CONFIG_ID );
		Options::delete( OptionDefaults::ORGANIZATION_ID );
		Options::delete( OptionDefaults::ORGANIZATION_NAME );
		Options::delete( OptionDefaults::ADS_ACCOUNT_ID );
		Options::delete( OptionDefaults::CONVERSION_ACCESS_TOKEN );
		Options::delete( OptionDefaults::PIXEL_ID );
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );

		return rest_ensure_response(
			array(
				'status' => $oauth_status,
			)
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
			'title'      => 'snapchat_config',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => 'The unique Snapchat config ID.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'id' ),
		);
	}
}
