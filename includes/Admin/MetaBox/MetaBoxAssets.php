<?php
/**
 * Conditional admin assets for plugin meta boxes on WooCommerce edit screens.
 *
 * Enqueues the channel-visibility bundle on the product edit screen and the
 * order-attribution bundle (Snapchat connect-account promo) on the order edit
 * screen, passing each the runtime data its script needs to decide whether to
 * render.
 *
 * @package SnapchatForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\MetaBox;

use SnapchatForWooCommerce\Utils\AssetLoader;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Handles admin script and style enqueues for plugin meta boxes.
 *
 * @since 0.1.0
 */
class MetaBoxAssets {

	/**
	 * Order attribution data resolver.
	 *
	 * @since 1.1.0
	 *
	 * @var OrderAttributionData
	 */
	protected OrderAttributionData $order_attribution_data;

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 *
	 * @param OrderAttributionData $order_attribution_data Order attribution data resolver.
	 */
	public function __construct( OrderAttributionData $order_attribution_data ) {
		$this->order_attribution_data = $order_attribution_data;
	}

	/**
	 * Registers WordPress admin-side hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueues the plugin meta box assets on their respective edit screens.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$this->enqueue_channel_visibility_assets();
		$this->enqueue_order_attribution_assets();
	}

	/**
	 * Enqueues the channel-visibility bundle and localizes its data on the product edit screen.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function enqueue_channel_visibility_assets(): void {
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

	/**
	 * Enqueues the order-attribution meta box assets on the order edit screen.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	protected function enqueue_order_attribution_assets(): void {
		if ( ! $this->order_attribution_data->is_wc_order_edit_screen() ) {
			return;
		}

		$onboarding_complete = Options::get( OptionDefaults::ONBOARDING_STATUS ) === 'connected';

		AssetLoader::enqueue_script( 'order-attribution', 'order-attribution' );
		AssetLoader::enqueue_style( 'order-attribution', 'order-attribution' );

		AssetLoader::localize_script(
			'order-attribution',
			'AdminData',
			array(
				'slug'          => 'snapwoo',
				'pluginVersion' => SNAPCHAT_FOR_WOOCOMMERCE_VERSION,
			)
		);

		AssetLoader::localize_script(
			'order-attribution',
			'MetaBoxData',
			array(
				'onboardingComplete'     => $onboarding_complete,
				'orderAttributionSource' => $this->order_attribution_data->get_order_attribution_source_for_edit_screen(),
			)
		);
	}
}
