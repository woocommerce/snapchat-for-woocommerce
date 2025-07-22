<?php
/**
 * Service class for declaring WooCommerce feature compatibility.
 *
 * This class integrates with WooCommerce's FeaturesUtil to declare compatibility
 * with experimental or advanced WooCommerce features, such as Custom Order Tables.
 * It registers itself early in the WooCommerce lifecycle to ensure compatibility
 * is declared before the feature is initialized.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Declares compatibility with specific WooCommerce features.
 *
 * This class is primarily used to inform WooCommerce that this plugin is
 * compatible with features such as Custom Order Tables, enabling better performance
 * or integration when those features are enabled.
 *
 * @since 0.1.0
 */
class Compatibility {
	/**
	 * Registers WordPress hooks required for declaring WooCommerce compatibility.
	 *
	 * Hooks into `before_woocommerce_init` to ensure compatibility declarations
	 * are registered before WooCommerce initializes its features.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'before_woocommerce_init', array( self::class, 'declare_compatibility' ) );
	}

	/**
	 * Declares plugin compatibility with WooCommerce features.
	 *
	 * Currently declares support for:
	 * - `custom_order_tables`: An experimental WooCommerce feature that stores
	 *   orders in custom database tables for improved performance.
	 *
	 * This method uses {@see FeaturesUtil::declare_compatibility()} to safely
	 * register compatibility and prevent admin notices about unsupported features.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function declare_compatibility(): void {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', SNAPCHAT_FOR_WOOCOMMERCE_FILE, true );
	}
}
