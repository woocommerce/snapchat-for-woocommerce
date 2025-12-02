<?php
/**
 * Plugin Name: Snapchat for WooCommerce
 * Description: Seamlessly integrates your WooCommerce store with Snapchat's powerful advertising platform, enabling you to reach millions of potential customers through engaging visual ads.
 * Version: 1.0.2
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: snapchat-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * PHP tested up to: 8.4
 * Requires at least: 6.7
 * Tested up to: 6.9
 * WC requires at least: 10.1
 * WC tested up to: 10.3
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package snapchat-for-woocommerce
 */

use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\ServiceContainer;
use SnapchatForWooCommerce\ServiceKey;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_VERSION' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_VERSION', '1.0.2' );
}

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_FILE' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_FILE', __FILE__ );
}

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_DIR' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_URL' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH', SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_DIR . 'js/build/' );
}

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_BUILD_URL' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_BUILD_URL', SNAPCHAT_FOR_WOOCOMMERCE_PLUGIN_URL . 'js/build/' );
}

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_DEBUG' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_DEBUG', false );
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

$export_service = ServiceContainer::get( ServiceKey::PRODUCT_EXPORT_SERVICE );

register_activation_hook(
	__FILE__,
	function () use ( $export_service ) {
		Options::preload_defaults();

		// Schedule recurring CSV export task when the plugin is activated.
		if ( 'connected' === Options::get( OptionDefaults::ONBOARDING_STATUS ) ) {
			$export_service->maybe_schedule_recurring_export();
		}
	}
);

register_deactivation_hook(
	__FILE__,
	function () use ( $export_service ) {
		// Unschedule all tasks related to product catalog export.
		$export_service->maybe_unschedule_export_jobs();
	}
);

add_action(
	'woocommerce_loaded',
	function () {
		\SnapchatForWooCommerce\Plugin::init();
	}
);
