<?php
/**
 * Product edit screen detection and inline data for the channel-visibility meta box bundle.
 *
 * @package SnapchatForWooCommerce\Admin\MetaBox
 * @since n.e.x.t
 */

namespace SnapchatForWooCommerce\Admin\MetaBox;

use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\OnboardingStatus;
use WC_Product;
use WP_Post;

/**
 * Gates and builds the payload for the Edit Product channel-visibility bundle.
 *
 * @since n.e.x.t
 */
final class ProductChannelVisibilityData {

	/**
	 * Builds the `channelVisibility` payload for `window.snapchatAdsMetaBoxData`.
	 *
	 * @since n.e.x.t
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

		$field_name = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );
		$raw_meta   = get_post_meta( $post->ID, $field_name, true );
		$catalog    = is_string( $raw_meta ) && '' !== $raw_meta ? $raw_meta : '1';

		return array(
			'onboardingComplete'   => OnboardingStatus::is_setup_completed(),
			'field_name'           => $field_name,
			'product_catalog_item' => $catalog,
			'product_is_visible'   => $product->is_visible(),
		);
	}
}
