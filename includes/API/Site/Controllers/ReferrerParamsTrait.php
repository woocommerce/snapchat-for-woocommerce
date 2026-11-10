<?php
/**
 * Shared handling for the `referrer_type`/`referrer_id` REST params.
 *
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use WP_REST_Request as Request;

/**
 * Carries in-product placement referrer params through an OAuth redirect.
 *
 * Connect requests pass the referrer they were opened with, and the controller
 * appends it to the URL the merchant returns to after authorization, so events
 * fired after the redirect can still be attributed to the placement.
 *
 * @since n.e.x.t
 */
trait ReferrerParamsTrait {

	/**
	 * Returns the REST param schema for `referrer_type` and `referrer_id`.
	 *
	 * @since n.e.x.t
	 *
	 * @return array
	 */
	protected function get_referrer_params(): array {
		return array(
			'referrer_type' => array(
				'description'       => __( 'Type of referrer that started this connection, kept across the authorization redirect.', 'snapchat-for-woocommerce' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'referrer_id'   => array(
				'description'       => __( 'ID of the referrer that started this connection, kept across the authorization redirect.', 'snapchat-for-woocommerce' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Appends the request's `referrer_type`/`referrer_id` params to a URL, when present.
	 *
	 * @since n.e.x.t
	 *
	 * @param string  $url     URL to append the params to.
	 * @param Request $request REST request carrying the params.
	 * @return string
	 */
	protected function append_referrer_args( string $url, Request $request ): string {
		$referrer_args = array_filter(
			array(
				'referrer_type' => $request->get_param( 'referrer_type' ),
				'referrer_id'   => $request->get_param( 'referrer_id' ),
			)
		);

		if ( empty( $referrer_args ) ) {
			return $url;
		}

		return add_query_arg( array_map( 'rawurlencode', $referrer_args ), $url );
	}
}
