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

use SnapchatForWoocommerce\Admin\Setup;
use SnapchatForWoocommerce\Config\AdPartnerConfig;
use SnapchatForWoocommerce\Infrastructure\WcsClient;
use SnapchatForWoocommerce\Infrastructure\JetpackAuthenticator;
use SnapchatForWoocommerce\API\ConnectionService;
use Automattic\Jetpack\Connection\Manager;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SNAPCHAT_FOR_WOOCOMMERCE_FILE' ) ) {
	define( 'SNAPCHAT_FOR_WOOCOMMERCE_FILE', __FILE__ );
}

if ( ! defined( 'SNAPCHAT_ADS_PLUGIN_DIR' ) ) {
	define( 'SNAPCHAT_ADS_PLUGIN_DIR', __FILE__ );
}

if ( ! defined( 'SNAPCHAT_ADS_PLUGIN_URL' ) ) {
	define( 'SNAPCHAT_ADS_PLUGIN_URL', __FILE__ );
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

// phpcs:disable WordPress.Files.FileName

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function snapchat_for_woocommerce_missing_wc_notice() {
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Snapchat For Woocommerce requires WooCommerce to be installed and active. You can download %s here.', 'snapchat_for_woocommerce' ), '<a href="https://woo.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

register_activation_hook( __FILE__, 'snapchat_for_woocommerce_activate' );

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function snapchat_for_woocommerce_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'snapchat_for_woocommerce_missing_wc_notice' );
		return;
	}
}

if ( ! class_exists( 'snapchat_for_woocommerce' ) ) :
	/**
	 * The snapchat_for_woocommerce class.
	 */
	class snapchat_for_woocommerce {
		/**
		 * This class instance.
		 *
		 * @var \snapchat_for_woocommerce single instance of this class.
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
			wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'snapchat_for_woocommerce' ), $this->version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'snapchat_for_woocommerce' ), $this->version );
		}

		/**
		 * Gets the main instance.
		 *
		 * Ensures only one instance can be loaded.
		 *
		 * @return \snapchat_for_woocommerce
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
endif;

add_action( 'plugins_loaded', 'snapchat_for_woocommerce_init', 10 );

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function snapchat_for_woocommerce_init() {
	load_plugin_textdomain( 'snapchat_for_woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'snapchat_for_woocommerce_missing_wc_notice' );
		return;
	}

	snapchat_for_woocommerce::instance();

}

add_action( 'rest_api_init', function () {
	$config     = new AdPartnerConfig();
	$wcs_client = new WcsClient( 'https://wcs-mock.mylocal' );
	$manager    = new Manager( 'snapchat-for-woocommerce' );
	$auth       = new JetpackAuthenticator( $manager );

	$connection = new ConnectionService( $config, $wcs_client, $auth );
	$connection->register_routes();
} );
