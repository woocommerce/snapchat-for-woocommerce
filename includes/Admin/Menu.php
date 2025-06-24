<?php
declare(strict_types=1);

namespace SnapchatForWooCommerce\Admin;

/**
 * Class Menu
 *
 * Registers Snapchat for WooCommerce menu entry under WooCommerce Marketing.
 *
 * @package SnapchatForWooCommerce\Admin
 */
class Menu {
	use MenuFixesTrait;

	/**
	 * Bootstrap the menu.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 10 );
	}

	/**
	 * Registers the Snapchat admin menu under WooCommerce > Marketing.
	 */
	public function register_menu(): void {
		$this->register_classic_submenu_page( [
			'id'       => 'snapchat-for-woocommerce',
			'title'    => __( 'Snapchat for WooCommerce', 'snapchat-for-woocommerce' ),
			'parent'   => 'woocommerce-marketing',
			'path'     => '/snapchat/start',
			'position' => 30,
		] );
	}
}
