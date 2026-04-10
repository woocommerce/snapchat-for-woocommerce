<?php
/**
 * Base payload for server-side Ad Partner Conversion events.
 *
 * Provides a minimal, consistent structure shared by all conversion events
 * triggered from WooCommerce (e.g., PURCHASE, ADD_TO_CART). Concrete event
 * classes should extend this base and merge/override fields as needed.
 *
 * @package SnapchatForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Tracking\ConversionEvent;

use SnapchatForWooCommerce\Utils\Helper;

/**
 * Defines common payload fields used by all Conversion events.
 *
 * Child classes (e.g., PurchaseEvent) should call {@see EventPayloadBase::build_payload()}
 * and then merge in their own event-specific fields and any overrides:
 *
 * `$base = parent::build_payload();`
 * `return array_merge( $base, $event_specific, $overrides );`
 *
 * @since 0.1.0
 */
class EventPayloadBase {
	/**
	 * Source describing where the event was observed.
	 * Common values include: WEB, APP, OFFLINE.
	 *
	 * @since 0.1.0
	 */
	public const ACTION_SOURCE = 'WEB';

	/**
	 * Build the base payload for an Ad Partner conversion event.
	 *
	 * This provides the common fields expected by Ads Conversions
	 * (and other Ad Partners) for all WooCommerce-triggered events.
	 * Child event classes should call this method and merge in their
	 * event-specific fields.
	 *
	 * The returned array includes:
	 * - `event_time`       — Epoch timestamp (ms preferred) of when the event occurred.
	 * - `integration`      — Identifier string for this integration (eg: `woocommerce-v1-0-0`).
	 * - `event_source_url` — The URL where the event originated (from {@see wc_get_raw_referer()}).
	 * - `action_source`    — The source channel describing where the event was observed.
	 *
	 * @since 0.1.0
	 *
	 * @return array Associative array of base payload fields.
	 */
	public function build_payload(): array {
		return array(
			'event_time'       => Helper::get_event_time(),
			'integration'      => Helper::get_integration_identifier(),
			'event_source_url' => (string) wc_get_raw_referer(),
			'action_source'    => self::ACTION_SOURCE,
		);
	}
}
