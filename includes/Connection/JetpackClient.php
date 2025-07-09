<?php
/**
 * Wrapper client for performing authenticated Jetpack remote requests.
 *
 * This class abstracts the Automattic\Jetpack\Connection\Client::remote_request()
 * method and provides a consistent interface for making authenticated HTTP requests
 * via Jetpack's secure connection to WordPress.com infrastructure.
 *
 * @package SnapchatForWooCommerce\Connection
 */

namespace SnapchatForWooCommerce\Connection;

use Automattic\Jetpack\Connection\Client;

/**
 * Provides a wrapper for Jetpack's remote_request method.
 *
 * This class is used to perform authenticated requests to the
 * WooCommerce Services (WCS) backend using Jetpack's connection system.
 * It encapsulates Jetpack's static method behind a class-based interface
 * for clean separation of concerns.
 *
 * @since 0.1.0
 */
class JetpackClient {

	/**
	 * Sends a remote HTTP request using Jetpack's connection client.
	 *
	 * Delegates to Automattic\Jetpack\Connection\Client::remote_request().
	 * Used for communicating with the WCS backend over a secure, authenticated channel.
	 *
	 * @since 0.1.0
	 *
	 * @param array             $args Arguments for the HTTP request.
	 * @param array|string|null $body The request body.
	 *
	 * @return array|WP_Error Response array on success, or WP_Error on failure.
	 */
	public function remote_request( array $args, $body = null ) {
		return Client::remote_request( $args, $body );
	}
}
