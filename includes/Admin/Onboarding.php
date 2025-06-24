<?php

namespace SnapchatForWooCommerce\Admin;

use SnapchatForWooCommerce\Utils\Helper;

/**
 * Class SetupMerchantCenter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class Onboarding {

	/**
	 * Register a service.
	 */
	public function register_hooks(): void {
		add_action(
			'admin_menu',
			function () {
				wc_admin_register_page(
					[
						'title'  => __( 'Snapchat Setup Wizard', 'snapchat-for-woocommerce' ),
						'parent' => '',
						'path'   => '/snapchat/onboarding',
						'id'     => Helper::with_prefix( 'onboarding' ),
					]
				);
			}
		);
	}
}
