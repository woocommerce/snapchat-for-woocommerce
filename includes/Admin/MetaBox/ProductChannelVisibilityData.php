<?php
/**
 * Product edit screen detection and channel-visibility inline data for the meta box bundle.
 *
 * @package SnapchatForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\MetaBox;

use SnapchatForWooCommerce\Admin\ProductMeta\ProductMetaFields;
use SnapchatForWooCommerce\Utils\Helper;
use WC_Product;
use WP_Post;

/**
 * Gate and payload for the Edit Product channel-visibility bundle.
 *
 * @since 0.1.0
 */
final class ProductChannelVisibilityData {

	/**
	 * Whether to enqueue the channel-visibility bundle on this request.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public static function should_enqueue_channel_visibility_bundle(): bool {
		return null !== self::get_channel_visibility_inline_block();
	}

	/**
	 * Builds the `channelVisibility` object for `snapchatAdsMetaBoxData`.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed>|null Null when not on a valid product edit context.
	 */
	public static function get_channel_visibility_inline_block(): ?array {
		global $post;

		if ( ! is_admin() ) {
			return null;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || 'product' !== $screen->id ) {
			return null;
		}

		if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
			return null;
		}

		$product = wc_get_product( $post->ID );

		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$field_name = Helper::with_prefix( ProductMetaFields::CATALOG_ITEM );
		$raw_meta   = get_post_meta( $post->ID, $field_name, true );
		$catalog    = is_string( $raw_meta ) && '' !== $raw_meta ? $raw_meta : '1';

		return array(
			'field_name'           => $field_name,
			'product_catalog_item' => $catalog,
			'product_is_visible'   => $product->is_visible(),
			'options'              => array(
				array(
					'value' => '1',
					'label' => __( 'Sync and show', 'snapchat-for-woocommerce' ),
				),
				array(
					'value' => '0',
					'label' => __( "Don't sync and show", 'snapchat-for-woocommerce' ),
				),
			),
		);
	}
}
