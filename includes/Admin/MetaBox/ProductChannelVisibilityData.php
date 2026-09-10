<?php
/**
 * Product edit screen detection and inline data for the channel-visibility meta box bundle.
 *
 * @package SnapchatForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\MetaBox;

use SnapchatForWooCommerce\Utils\OnboardingStatus;
use WC_Product;
use WP_Post;

/**
 * Gates and builds the payload for the Edit Product channel-visibility bundle.
 *
 * @since 0.1.0
 */
final class ProductChannelVisibilityData {

	/**
	 * Builds the `channelVisibility` payload for `window.snapchatAdsMetaBoxData`.
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

		return array(
			'onboardingComplete' => OnboardingStatus::is_setup_completed(),
		);
	}
}
