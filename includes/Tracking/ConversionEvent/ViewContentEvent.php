<?php
/**
 * Server-side Ad Partner Conversion event representing an "View Content" action.
 *
 * @package SnapchatForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Tracking\ConversionEvent;

/**
 * Constructs a Conversion request payload for the VIEW_CONTENT event type.
 *
 * This class captures minimal single product page data for tracking view content conversions.
 *
 * @since 0.1.0
 */
final class ViewContentEvent implements ConversionEventInterface {

	/**
	 * Product ID being added to the cart.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $product_id;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 */
	public function __construct( int $product_id ) {
		$this->product_id = $product_id;
	}

	/**
	 * Builds the raw Conversion payload for the Ad Partner.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Overrideable payload args.
	 *
	 * @return array<string,mixed> Conversion event payload.
	 */
	public function build_payload( array $args = array() ): array {
		$product = wc_get_product( $this->product_id );

		if ( ! $product instanceof \WC_Product ) {
			return array();
		}

		if ( $product->is_type( 'variation' ) ) {
			$content_type = 'product_group';
		} elseif ( $product->is_type( 'grouped' ) ) {
			$content_type = 'product_group';
		} else {
			$content_type = 'product';
		}

		$default = array(
			'event_name'       => 'VIEW_CONTENT',
			'event_time'       => time(),
			'event_source_url' => wc_get_raw_referer(),
			'action_source'    => 'WEB',
			'user_data'        => array(),
			'custom_data'      => array(
				'content_ids'  => array( $product->get_sku() ),
				'content_type' => $content_type,
				'currency'     => get_woocommerce_currency(),
			),
		);

		return array_merge( $default, $args );
	}
}
