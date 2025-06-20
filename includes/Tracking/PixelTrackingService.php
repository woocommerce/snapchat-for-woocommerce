<?php
/**
 * Service class for managing Ad Partner's Pixel tracking in WooCommerce.
 *
 * This class acts as the integration point between the WordPress/WooCommerce lifecycle
 * and Ad Partner pixel injection logic. It registers hooks to automatically inject
 * the pixel when appropriate and provides runtime checks for whether tracking is enabled.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Config;
use WC_Product;

/**
 * Handles the registration of pixel-related hooks and provides access to tracking status.
 *
 * This service registers frontend and REST API hooks to support pixel injection behavior.
 * Pixel rendering is delegated to a {@see PixelTrackerInterface} implementation. It also provides
 * a utility method to check whether tracking is currently enabled via plugin settings.
 *
 * Dependencies:
 * - {@see PixelTrackerInterface}: Determines if and how the pixel should be injected.
 * - {@see OptionsStore} and {@see OptionDefaults}: Used to read tracking settings.
 *
 * @since 0.1.0
 */
final class PixelTrackingService implements ServiceStatusInterface {
	/**
	 * Collected product data for localization.
	 *
	 * @var array
	 */
	protected array $products = array();

	/**
	 * Instance of the pixel tracker responsible for rendering the pixel.
	 *
	 * @var PixelTrackerInterface
	 */
	private PixelTrackerInterface $tracker;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PixelTrackerInterface $tracker Instance implementing the logic to inject the tracking pixel.
	 */
	public function __construct( PixelTrackerInterface $tracker ) {
		$this->tracker = $tracker;
	}

	/**
	 * Registers WordPress hooks used for pixel injection and route initialization.
	 *
	 * - Hooks into `wp_head` to optionally output the pixel on frontend pages.
	 * - Registers global site tag logic.
	 * - Enqueues external tracking assets.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		add_action(
			'wp_head',
			array( $this->tracker, 'maybe_inject_pixel' )
		);

		add_action(
			'wp_footer',
			array(
				$this,
				'populate_tracking_data',
			)
		);

		add_filter(
			'woocommerce_loop_add_to_cart_link',
			function ( $link, $product ) {
				if ( $product instanceof WC_Product ) {
					$this->add_product_data( $product );
				}
				return $link;
			},
			10,
			2
		);

		add_action(
			'woocommerce_after_add_to_cart_button',
			function () {
				global $product;

				if ( $product instanceof WC_Product ) {
					$this->add_product_data( $product );
				}
			}
		);

		add_action(
			'woocommerce_after_single_product',
			array( $this->tracker, 'track_view_content_event' )
		);

		add_action(
			'woocommerce_before_thankyou',
			array( $this->tracker, 'track_purchase_event' )
		);
	}

	/**
	 * Determines whether Pixel tracking is currently enabled.
	 *
	 * This checks the persisted plugin option configured via the admin interface or defaults.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if pixel tracking is enabled; false otherwise.
	 */
	public static function is_enabled(): bool {
		return (bool) Options::get( OptionDefaults::PIXEL_ENABLED );
	}

	/**
	 * Returns the localized data structure to be passed to the frontend via JavaScript.
	 *
	 * @since 0.1.0
	 *
	 * @return array Associative array of currency settings and collected product data.
	 */
	public function get_pixel_data(): array {
		$data = array(
			'currency_minor_unit' => wc_get_price_decimals(),
			'currency'            => get_woocommerce_currency(),
			'products'            => $this->products,
		);

		return $data;
	}

	/**
	 * Injects localized pixel tracking data into the page footer.
	 *
	 * This method outputs a `<script>` block that attaches collected pixel metadata
	 * (currency, product prices, etc.) to a global JavaScript variable defined by the
	 * plugin. This data is later used by frontend scripts to dispatch tne Ad Partner Pixel
	 * tracking events.
	 *
	 * The output is injected via `wp_add_inline_script` and attached to the
	 * pixel tracking script handle defined in {@see Config::ASSET_HANDLE_PREFIX}.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function populate_tracking_data() {
		wp_add_inline_script(
			Config::ASSET_HANDLE_PREFIX . 'tracking',
			'
			window.snapchatAdsTrackingData = window.snapchatAdsTrackingData || {};
			window.snapchatAdsTrackingData = Object.assign( window.snapchatAdsTrackingData, ' . wp_json_encode( array( 'pixel_data' => $this->get_pixel_data() ) ) . ' );
			'
		);
	}

	/**
	 * Adds product-specific tracking metadata to the internal product list.
	 *
	 * This method is called during both loop rendering and single product display,
	 * collecting price information for each product encountered. The data is later
	 * localized for use by frontend tracking scripts via {@see get_pixel_data()}.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product WooCommerce product instance whose data should be collected.
	 * @return void
	 */
	protected function add_product_data( WC_Product $product ): void {
		$product_id = $product->get_id();

		$this->products[ $product_id ] = array(
			'price' => wc_get_price_to_display( $product ),
		);
	}
}
