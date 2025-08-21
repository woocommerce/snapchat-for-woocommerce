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
 * This class captures minimal single product page data for tracking
 * view content conversions.
 *
 * @since 0.1.0
 */
final class ViewContentEvent extends EventPayloadBase implements ConversionEventInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'VIEW_CONTENT';

	/**
	 * Product ID being viewed.
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

		$base    = parent::build_payload();
		$default = array(
			'event_name'  => self::ID,
			'user_data'   => array(),
			'custom_data' => array(
				'content_ids'  => array( (string) $product->get_id() ),
				'content_type' => $content_type,
				'currency'     => get_woocommerce_currency(),
				'contents'     => array(
					array(
						'id'         => (string) $product->get_id(),
						'quantity'   => '1',
						'item_price' => (string) $product->get_price(),
					),
				),
			),
		);

		return array_merge( $base, $default, $args );
	}
}
