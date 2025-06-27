<?php
/**
 * Registers the Snapchat Setup Wizard page in Woo Admin.
 *
 * This class adds a standalone React-powered admin page at
 * `/wp-admin/admin.php?page=wc-admin&path=/snapchat/onboarding`
 * which is typically used for onboarding workflows such as authentication,
 * ad account selection, and initial plugin setup.
 *
 * @package SnapchatForWooCommerce\Admin
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin;

use SnapchatForWooCommerce\Utils\Helper;

/**
 * Registers the WooCommerce Admin onboarding page for Snapchat.
 *
 * This class adds a Woo Admin-powered page without a parent menu item.
 * The page is expected to be registered on the JS side via `addFilter( 'woocommerce_admin_pages_list' )`
 * and rendered using a React component.
 *
 * @since 0.1.0
 */
class Onboarding {

	/**
	 * Hooks the onboarding page registration into WooCommerce Admin.
	 *
	 * Registers the onboarding route using `wc_admin_register_page()` and assigns it a
	 * unique ID using the plugin prefix. This page is accessible via direct URL only and
	 * does not appear in the admin menu.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action(
			'admin_menu',
			function () {
				wc_admin_register_page(
					array(
						'title'  => __( 'Snapchat Setup Wizard', 'snapchat-for-woocommerce' ),
						'parent' => '',
						'path'   => '/snapchat/onboarding',
						'id'     => Helper::with_prefix( 'onboarding' ),
					)
				);
			}
		);
	}
}
