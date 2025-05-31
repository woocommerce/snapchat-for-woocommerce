<?php

namespace SnapchatForWoocommerce\Admin;

/**
 * SnapchatForWoocommerce Setup Class
 */
class Setup {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Load all necessary dependencies.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		if ( ! method_exists( 'Automattic\WooCommerce\Admin\PageController', 'is_admin_or_embed_page' ) ||
		! \Automattic\WooCommerce\Admin\PageController::is_admin_or_embed_page()
		) {
			return;
		}

		$script_path       = '/build/index.js';
		$script_asset_path = dirname( SNAPCHAT_FOR_WOOCOMMERCE_FILE ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => filemtime( $script_path ),
		);
		$script_url        = plugins_url( $script_path, SNAPCHAT_FOR_WOOCOMMERCE_FILE );

		wp_register_script(
			'snapchat-for-woocommerce',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_register_style(
			'snapchat-for-woocommerce',
			plugins_url( '/build/index.css', SNAPCHAT_FOR_WOOCOMMERCE_FILE ),
			// Add any dependencies styles may have, such as wp-components.
			array(),
			filemtime( dirname( SNAPCHAT_FOR_WOOCOMMERCE_FILE ) . '/build/index.css' )
		);

		wp_enqueue_script( 'snapchat-for-woocommerce' );
		wp_enqueue_style( 'snapchat-for-woocommerce' );
	}

	/**
	 * Register page in wc-admin.
	 *
	 * @since 1.0.0
	 */
	public function register_page() {

		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}

		wc_admin_register_page(
			array(
				'id'     => 'snapchat_for_woocommerce-example-page',
				'title'  => __( 'Snapchat For Woocommerce', 'snapchat_for_woocommerce' ),
				'parent' => 'woocommerce',
				'path'   => '/snapchat-for-woocommerce',
			)
		);
	}
}
