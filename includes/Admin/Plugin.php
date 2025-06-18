<?php
/**
 * Plugin Admin: Main Plugin Class.
 *
 * @package SnapchatForWooCommerce\Admin
 */

namespace SnapchatForWooCommerce\Admin;

/**
 * The Plugin class.
 */
class Plugin {
	/**
	 * This class instance.
	 *
	 * @var \Plugin single instance of this class.
	 */
	private static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			new Setup();
		}
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'snapchat-for-woocommerce' ), $this->version );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'snapchat-for-woocommerce' ), $this->version );
	}

	/**
	 * Gets the main instance.
	 *
	 * Ensures only one instance can be loaded.
	 *
	 * @return Plugin
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
