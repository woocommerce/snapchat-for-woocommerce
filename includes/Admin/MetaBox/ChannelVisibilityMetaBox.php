<?php
/**
 * Registers the Channel visibility meta box on the Edit Product screen.
 *
 * Supports two modes:
 * - Cohabit: another supported channel plugin has already registered the shared
 *   `channel_visibility` meta box; Snapchat's row is injected into that box via React.
 * - Standalone: no shared box exists, so Snapchat registers its own meta box.
 *
 * @package SnapchatForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\MetaBox;

/**
 * Handles Channel visibility meta box registration on the product edit screen.
 *
 * @since 0.1.0
 */
class ChannelVisibilityMetaBox {

	/**
	 * Shared meta box id used to detect a box already registered by another channel plugin.
	 *
	 * @since 0.1.0
	 */
	public const SHARED_BOX_ID = 'channel_visibility';

	/**
	 * Snapchat's own standalone meta box id, registered when no shared box exists.
	 *
	 * @since 0.1.0
	 */
	public const BOX_ID = 'snapchat-channel-visibility';

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'maybe_register' ), 999, 2 );
	}

	/**
	 * Registers Snapchat's own Channel visibility meta box only when no shared box exists.
	 *
	 * Runs at priority 999 so channel plugins registering at their default priority
	 * have already claimed the shared box. In cohabit mode the React bundle injects
	 * Snapchat's row into the existing box; in standalone mode Snapchat owns the box.
	 *
	 * @since 0.1.0
	 *
	 * @param string   $post_type Current post type.
	 * @param \WP_Post $post      Current post object.
	 * @return void
	 */
	public function maybe_register( string $post_type, $post ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wp_meta_boxes;

		if ( 'product' !== $post_type ) {
			return;
		}

		if ( $this->box_registered( (array) $wp_meta_boxes ) ) {
			return;
		}

		add_meta_box(
			self::BOX_ID,
			__( 'Channel visibility', 'snapchat-for-woocommerce' ),
			array( $this, 'render' ),
			'product',
			'side'
		);
	}

	/**
	 * Renders the React mount point for the Channel visibility widget.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render(): void {
		echo '<div id="snapchat-channel-visibility-box"></div>';
	}

	/**
	 * Checks whether the shared Channel visibility box is already registered for products.
	 *
	 * @since 0.1.0
	 *
	 * @param array $wp_meta_boxes Global meta boxes registry.
	 * @return bool
	 */
	private function box_registered( array $wp_meta_boxes ): bool {
		if ( empty( $wp_meta_boxes['product'] ) ) {
			return false;
		}

		foreach ( $wp_meta_boxes['product'] as $contexts ) {
			foreach ( $contexts as $priorities ) {
				if ( array_key_exists( self::SHARED_BOX_ID, $priorities ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
