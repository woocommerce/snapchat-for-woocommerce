<?php
/**
 * Handles logging of conversion tracking events and failures.
 *
 * This class integrates with the WooCommerce logging system to log issues
 * related to the transmission of conversion events to the Ad Partner's API.
 * It assigns different severity levels based on the event type.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use WC_Logger_Interface;
use SnapchatForWooCommerce\Tracking\ConversionEvent;

/**
 * Service class for logging failed or significant conversion tracking events.
 *
 * Logs are directed to the WooCommerce logger system using impact-sensitive levels:
 * - `critical` for purchases
 * - `high` for add-to-cart, start-checkout, and view-content
 * - `info` fallback for less critical events
 *
 * The logger can be used to monitor integration health and troubleshoot failed API calls.
 *
 * @since 0.1.0
 */
class ConversionEventLogger {

	/**
	 * WooCommerce logger instance.
	 *
	 * @var WC_Logger_Interface
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param WC_Logger_Interface $logger WooCommerce logger instance.
	 */
	public function __construct( WC_Logger_Interface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Logs the outcome of a conversion event based on HTTP status code.
	 *
	 * - If the status code is in the 2xx range, logs at `info` level as success.
	 * - Otherwise, logs at an appropriate severity based on event type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $event_name  Event identifier (e.g. 'PURCHASE', 'ADD_CART').
	 * @param ?int   $status_code HTTP status code returned from the API.
	 * @param array  $context     Optional structured context.
	 * @return void
	 */
	public function log_event( string $event_name, ?int $status_code, array $context = array() ): void {
		$is_success = $status_code >= 200 && $status_code < 300;

		$message = sprintf(
			'Conversion event "%s" %s with status code %d.',
			$event_name,
			$is_success ? 'succeeded' : 'failed',
			$status_code
		);

		$log_level = $is_success
			? 'info'
			: $this->determine_log_level( $event_name );

		if ( method_exists( $this->logger, $log_level ) ) {
			$this->logger->$log_level( $message, $context );
		} else {
			$this->logger->info( "[Fallback] $message", $context );
		}
	}

	/**
	 * Determine the appropriate log level based on event name.
	 *
	 * @param string $event_name The name of the conversion event (e.g., 'PURCHASE', 'ADD_CART').
	 */
	protected function determine_log_level( string $event_name ): string {
		switch ( $event_name ) {
			case ConversionEvent\PurchaseEvent::ID:
				return 'critical';
			case ConversionEvent\StartCheckoutEvent::ID:
			case ConversionEvent\AddToCartEvent::ID:
			case ConversionEvent\ViewContentEvent::ID:
			case ConversionEvent\PageViewEvent::ID:
				return 'warning';
		}

		return 'info';
	}
}
