<?php
/**
 * Registers the Ad Partner's menu entry in the WooCommerce admin.
 *
 * This class ensures that the plugin’s admin page appears under
 * WooCommerce > Marketing as a React-powered view integrated via Woo Admin.
 * It uses {@see MenuFixesTrait} to register the page using classic WordPress
 * submenu functions for compatibility with Woo Admin routing.
 *
 * @package SnapchatForWooCommerce\Admin
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin;

/**
 * Registers the WooCommerce Marketing submenu entry for the Ad Partner.
 *
 * This class integrates a React-powered admin page under the "Marketing" menu
 * using the `register_classic_submenu_page()` helper in {@see MenuFixesTrait}.
 * The page renders via Woo Admin’s routing system (`/wp-admin/admin.php?page=wc-admin&path=/snapchat/start`)
 * while appearing natively within the classic admin UI.
 *
 * @since 0.1.0
 */
class Menu {
	use MenuFixesTrait;

	/**
	 * Hooks the menu registration callback into the `admin_menu` action.
	 *
	 * Called by the admin setup bootstrap class during plugin initialization.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 10 );
	}

	/**
	 * Registers the Snapchat submenu under WooCommerce > Marketing.
	 *
	 * Uses `MenuFixesTrait` to ensure proper submenu behavior, React page path resolution,
	 * and position ordering. The linked page should be registered with Woo Admin JS routing
	 * (e.g., via `PageController::connect_page()` on the server and JS `addFilter()`).
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->register_classic_submenu_page(
			array(
				'id'       => 'snapchat-for-woocommerce',
				'title'    => __( 'Snapchat', 'snapchat-for-woocommerce' ),
				'parent'   => 'woocommerce-marketing',
				'path'     => '/snapchat/start',
				'position' => 30,
			)
		);
	}
}
