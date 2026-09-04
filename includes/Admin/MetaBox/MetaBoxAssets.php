<?php
/**
 * Enqueues meta box assets for the WooCommerce order edit screen.
 *
 * Loads the order-attribution bundle that renders the Snapchat connect-account
 * promo within the Order Attribution meta box, and passes the runtime data the
 * script needs to decide whether to render.
 *
 * @package SnapchatForWooCommerce\Admin\MetaBox
 * @since 1.1.0
 */

namespace SnapchatForWooCommerce\Admin\MetaBox;

use SnapchatForWooCommerce\Utils\AssetLoader;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Handles admin script and style enqueues for plugin meta boxes.
 *
 * @since 1.1.0
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
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueues the order-attribution meta box assets on the order edit screen.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
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
				'adAccountId'   => Options::get( OptionDefaults::AD_ACCOUNT_ID ),
				'status'        => Options::get( OptionDefaults::ONBOARDING_STATUS ),
				'step'          => Options::get( OptionDefaults::ONBOARDING_STEP ),
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
