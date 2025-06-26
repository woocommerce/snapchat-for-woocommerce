<?php
/**
 * Interface for server-side conversion events tracked via Conversion.
 *
 * Defines the required contract for all Conversion event payload builders.
 *
 * @package SnapchatForWooCommerce\Tracking
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Tracking\ConversionEvent;

/**
 * Represents a server-side Ad Partner conversion event.
 *
 * All conversion events must be able to generate a payload compatible
 * with the Ad Partner Conversions API.
 *
 * @since 0.1.0
 */
interface ConversionEventInterface {

	/**
	 * Builds the payload to send to the Ad Partner Conversions API.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> Raw event payload.
	 */
	public function build_payload(): array;
}
