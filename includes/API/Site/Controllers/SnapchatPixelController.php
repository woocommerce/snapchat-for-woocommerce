<?php
/**
 * REST controller for managing Snapchat Pixels.
 *
 * This controller allows retrieving, selecting, and getting the selected Pixel
 * associated with the authenticated merchant's Snapchat account.
 *
 * It fetches Pixel data from the Snapchat API
 * via the WooCommerce Connect Server (WCS) and stores relevant selections
 * in the local WordPress options.
 *
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the `/pixel` endpoint.
 *
 * @since 0.1.0
 */
class SnapchatPixelController extends RESTBaseController {

	/**
	 * Registers REST API routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * GET /pixel
		 * - Returns the pixel ID
		 */
		register_rest_route(
			Config::REST_NAMESPACE . '/snapchat',
			'/pixel',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_pixel' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'pixel_schema' ),
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
	public function get_pixel() {
		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		return rest_ensure_response(
			array(
				'id' => $pixel_id,
			)
		);
	}

	/**
	 * Returns the JSON schema for the `/pixel` REST endpoint.
	 *
	 * This schema defines a single object with a required `id` property
	 * representing the selected Snapchat pixel. It is used to validate
	 * incoming POST payloads and document the expected data format.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for a selected pixel.
	 */
	public function pixel_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'snapchat_pixel',
			'type'       => 'object',
			'properties' => array(
				'id'   => array(
					'description' => 'The unique ID of the selected Snapchat Pixel.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'id' ),
		);
	}
}
