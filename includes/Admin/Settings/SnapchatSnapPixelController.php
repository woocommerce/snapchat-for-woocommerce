<?php
/**
 * REST controller for managing Ad Partner Pixels.
 *
 * This controller fetches, stores, and returns the selected Pixel
 * for the current merchant's ad account.
 *
 * @package SnapchatForWooCommerce\Admin\Settings
 */

namespace SnapchatForWooCommerce\Admin\Settings;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * Controller for the `/pixels` endpoint.
 *
 * @since 0.1.0
 */
class SnapchatSnapPixelController extends SettingsBaseController {

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
		$this->namespace = 'wc/sfw/snapchat';
		$this->rest_base = 'pixels';
		$this->wcs       = $wcs;
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
		 * GET /wp-json/wc/sfw/snapchat/pixels?ads_account_id
		 * - Returns filtered array of Pixels under the `ads_account_id`
		 *
		 * DELETE /wp-json/wc/sfw/snapchat/pixels?ads_account_id
		 * - Deletes Options::delete( OptionDefaults::PIXELS );
		 * - Deletes Options::delete( OptionDefaults::PIXEL_ID );
		 * - Deletes Transients::delete( TransientDefaults::PIXEL_SCRIPT );
		 */
		register_rest_route(
			$this->namespace,
			'/pixels',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_pixels' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'ads_account_id' => array(
							'description' => 'Ad Account ID for which to fetch pixels.',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'schema'              => array( $this, 'pixels_schema' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_pixels' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'pixels_schema' ),
			),
		);

		/**
		 * GET /wp-json/wc/sfw/snapchat/pixel
		 * - Returns the value from OptionDefaults::PIXEL_ID
		 *
		 * POST /wp-json/wc/sfw/snapchat/pixel
		 * - Saves the value from OptionDefaults::PIXEL_ID
		 * - payload: { id: 'abc123' }
		 */
		register_rest_route(
			$this->namespace,
			'/pixel',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_pixel_id' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'set_pixel_id' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Pixel ID to save as selected.',
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'pixel_id_schema' ),
			)
		);

		/**
		 * GET /wp-json/wc/sfw/snapchat/pixels/script
		 * - Returns the value from OptionDefaults::PIXEL_SCRIPT
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/script',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_pixel_script' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);
	}

	/**
	 * Returns a list of available pixels for the provided ad account ID.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_pixels( $request ) {
		$pixels = Options::get( OptionDefaults::PIXELS );

		if ( ! empty( $pixels ) ) {
			return rest_ensure_response( $pixels );
		}

		$ad_account_id = sanitize_text_field( $request->get_param( 'ads_account_id' ) );
		$response      = $this->wcs->proxy_get(
			"/ads/v1/adaccounts/{$ad_account_id}/pixels",
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response->get_data();

		if ( empty( $data['pixels'] ) ) {
			return rest_ensure_response( array() );
		}

		$pixels = array_map(
			fn( $entry ) => array(
				'id'               => $entry['pixel']['id'],
				'name'             => $entry['pixel']['name'],
				'pixel_javascript' => $entry['pixel']['pixel_javascript'],
			),
			$data['pixels']
		);

		Options::set( OptionDefaults::PIXELS, $pixels );

		return rest_ensure_response( $pixels );
	}

	/**
	 * Saves the selected pixel ID and its associated JavaScript.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_pixel_id( $request ) {
		$selected_id = sanitize_text_field( $request->get_param( 'id' ) );
		$pixels      = Options::get( OptionDefaults::PIXELS );

		$pixel = current(
			array_filter(
				$pixels,
				fn( $entry ) => (string) $entry['id'] === (string) $selected_id
			)
		);

		if ( empty( $pixel ) ) {
			return new WP_Error(
				'pixel_not_found',
				'Selected Pixel ID does not match any known pixels.',
				array( 'status' => 400 )
			);
		}

		Options::set( OptionDefaults::PIXEL_ID, $selected_id );
		Transients::set( TransientDefaults::PIXEL_SCRIPT, $pixel['pixel_javascript'] );

		return rest_ensure_response( array( 'id' => $selected_id ) );
	}

	/**
	 * Returns the saved pixel ID, if available.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_pixel_id() {
		return rest_ensure_response(
			array( 'id' => Options::get( OptionDefaults::PIXEL_ID ) )
		);
	}

	/**
	 * Deletes all stored pixel data.
	 *
	 * This includes the list of available pixels, the selected pixel ID,
	 * and the associated JavaScript snippet stored in a transient.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response Response indicating successful deletion.
	 */
	public function delete_pixels() {
		Options::delete( OptionDefaults::PIXELS );
		Options::delete( OptionDefaults::PIXEL_ID );
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );

		return rest_ensure_response(
			array( 'deleted' => true )
		);
	}

	/**
	 * Returns the saved pixel script.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_pixel_script() {
		$script = Transients::get( TransientDefaults::PIXEL_SCRIPT );

		if ( empty( $script ) ) {
			$script = '';
		}

		return rest_ensure_response( $script );
	}

	/**
	 * Returns the JSON schema for the `/pixels` REST route.
	 *
	 * This schema defines an array of Pixel objects associated with a
	 * Snapchat Ad Account. Each pixel object includes a required `id`,
	 * `name`, and `pixel_javascript` field. This schema is used for
	 * validating API responses and for documentation purposes.
	 *
	 * The structure ensures that any list of pixels returned from the
	 * Snapchat API or stored locally conforms to a predictable format
	 * usable by frontend clients and external tools.
	 *
	 * Example output:
	 * [
	 *     {
	 *         "id": "1234",
	 *         "name": "Main Store Pixel",
	 *         "pixel_javascript": "<script>...</script>"
	 *     }
	 * ]
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON schema for an array of Snapchat Pixel objects.
	 */
	public function pixels_schema(): array {
		return array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'snapchat_pixels',
			'type'    => 'array',
			'items'   => array(
				'type'       => 'object',
				'properties' => array(
					'id'               => array(
						'description' => 'The unique ID of the Ad Partner pixel.',
						'type'        => 'string',
					),
					'name'             => array(
						'description' => 'The name of the pixel.',
						'type'        => 'string',
					),
					'pixel_javascript' => array(
						'description' => 'The raw JavaScript snippet used for tracking.',
						'type'        => 'string',
					),
				),
				'required'   => array( 'id', 'name', 'pixel_javascript' ),
			),
		);
	}

	/**
	 * Returns the JSON schema for the `/pixel` REST route.
	 *
	 * This schema defines an object with a required `id` field representing
	 * the selected Pixel ID. It is used for validating and documenting the
	 * payload of POST requests and the structure of GET responses.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON schema for the selected pixel ID.
	 */
	public function pixel_id_schema(): array {
		return array(
			'title'      => 'snapchat_pixel_id',
			'type'       => 'object',
			'required'   => array( 'id' ),
			'properties' => array(
				'id' => array(
					'description' => 'The selected pixel ID.',
					'type'        => 'string',
				),
			),
		);
	}
}
