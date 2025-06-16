<?php
/**
 * Plugin Name: Snapchat For Woocommerce
 * Version: 0.1.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: snapchat-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package snapchat-for-woocommerce
 */

use SnapchatForWooCommerce\Utils\OptionsStore;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_FILE' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_FILE', __FILE__ );
}

if ( ! defined( 'SNAPCHAT_ADS_PLUGIN_DIR' ) ) {
	define( 'SNAPCHAT_ADS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SNAPCHAT_ADS_PLUGIN_URL' ) ) {
	define( 'SNAPCHAT_ADS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SNAPCHAT_ADS_PLUGIN_BUILD_PATH' ) ) {
	define( 'SNAPCHAT_ADS_PLUGIN_BUILD_PATH', SNAPCHAT_ADS_PLUGIN_DIR . 'build/' );
}

if ( ! defined( 'SNAPCHAT_ADS_PLUGIN_BUILD_URL' ) ) {
	define( 'SNAPCHAT_ADS_PLUGIN_BUILD_URL', SNAPCHAT_ADS_PLUGIN_URL . 'build/' );
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

register_activation_hook(
	__FILE__,
	function () {
		OptionsStore::preload_defaults();
	}
);

add_action(
	'woocommerce_loaded',
	function () {
		\SnapchatForWooCommerce\Plugin::init();
	}
);
