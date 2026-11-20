<?php
/**
 * Shared builders for tests that queue and send conversion events.
 *
 * @package SnapchatForWooCommerce\Tests\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Tracking;

use ActionScheduler_Store;
use WP_Error;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Helper;

trait ConversionSendTestHelpers {

	/**
	 * Returns pending send actions, keyed by action ID, optionally for one order.
	 *
	 * @param int|null $order_id WooCommerce order ID, or null for every send.
	 * @return \ActionScheduler_Action[]
	 */
	private function pending_sends( ?int $order_id = null ): array {
		$actions = as_get_scheduled_actions(
			array(
				'hook'     => Helper::with_prefix( 'send_conversion_event' ),
				'group'    => Config::PLUGIN_SLUG,
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1,
			),
			'objects'
		);

		if ( null === $order_id ) {
			return $actions;
		}

		return array_filter(
			$actions,
			fn( $action ) => ( $action->get_args()['args']['order_id'] ?? 0 ) === $order_id
		);
	}

	/**
	 * Builds a transport error such as a timeout.
	 *
	 * @return WP_Error
	 */
	private function transport_error(): WP_Error {
		return new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
	}

	/**
	 * Builds the WP_Error WcsClient returns for a non-2xx response.
	 *
	 * @param int $code HTTP status code.
	 * @return WP_Error
	 */
	private function http_error( int $code ): WP_Error {
		return new WP_Error(
			Helper::with_prefix( 'request_failed' ),
			'Request failed',
			array(
				'response' => array(
					'code'    => $code,
					'message' => '',
				),
				'body'     => '',
			)
		);
	}
}
