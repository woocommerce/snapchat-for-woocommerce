<?php
/**
 * Conditional admin assets for the Channel visibility widget on the Edit Product screen.
 *
 * @package SnapchatForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\MetaBox;

use SnapchatForWooCommerce\Utils\AssetLoader;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Enqueues the channel-visibility bundle on the product edit screen and inlines its data.
 *
 * @since 0.1.0
 */
class MetaBoxAssets {

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueues the channel-visibility bundle and localizes its data on the product edit screen.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$channel_visibility = ProductChannelVisibilityData::get_channel_visibility_inline_block();

		if ( null === $channel_visibility ) {
			return;
		}

		AssetLoader::enqueue_script( 'channel-visibility-meta-box', 'channel-visibility-meta-box' );

		// The main admin bundle is not loaded on the product edit screen, so the Redux
		// store and tracking helpers read their base data from this payload.
		AssetLoader::localize_script(
			'channel-visibility-meta-box',
			'AdminData',
			array(
				'slug'          => 'snapwoo',
				'pluginVersion' => SNAPCHAT_FOR_WOOCOMMERCE_VERSION,
				'adAccountId'   => Options::get( OptionDefaults::AD_ACCOUNT_ID ),
				'status'        => Options::get( OptionDefaults::ONBOARDING_STATUS ),
				'step'          => Options::get( OptionDefaults::ONBOARDING_STEP ),
			)
		);

		AssetLoader::localize_script(
			'channel-visibility-meta-box',
			'MetaBoxData',
			$channel_visibility
		);
	}
}
