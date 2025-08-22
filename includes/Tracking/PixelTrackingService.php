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
use SnapchatForWooCommerce\Utils\Helper;
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
	 * Registers WordPress hooks used for pixel injection and WooCommerce event hooks.
	 *
	 * - Hooks into `wp_head` to optionally inject the pixel.
	 * - Enqueues inline pixel data in `wp_footer`.
	 * - Hooks into WooCommerce templates to collect product data for pixel events.
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
			array( $this, 'populate_tracking_data' )
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
			'wp_footer',
			array( $this->tracker, 'track_purchase_event' ),
			11
		);

		add_action(
			'woocommerce_after_add_to_cart_quantity',
			array( $this, 'render_event_id_field' )
		);
	}

	/**
	 * Checks whether Pixel tracking is enabled in plugin settings.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if tracking is enabled; false otherwise.
	 */
	public static function is_enabled(): bool {
		return 'yes' === Options::get( OptionDefaults::PIXEL_ENABLED );
	}

	/**
	 * Returns pixel-specific metadata to be localized to the frontend.
	 *
	 * Includes currency settings and collected product pricing info.
	 *
	 * @since 0.1.0
	 *
	 * @return array Associative array of pixel metadata.
	 */
	public function get_pixel_data(): array {
		return array(
			'currency_minor_unit' => wc_get_price_decimals(),
			'currency'            => get_woocommerce_currency(),
			'products'            => $this->products,
		);
	}

	/**
	 * Injects inline tracking data into the page footer.
	 *
	 * Conditionally populates tracking data with relevant event payloads
	 * such as `VIEW_CONTENT`, `START_CHECKOUT`, and `PAGE_VIEW`, then
	 * localizes it to the frontend as a global JS variable.
	 *
	 * @since 0.1.0
	 */
	public function populate_tracking_data(): void {
		$tracking_data = array(
			'pixel_data' => $this->get_pixel_data(),
		);

		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		if ( is_product() ) {
			$this->filter_view_content_event_data( $tracking_data );
		}

		if ( is_checkout() && ! is_order_received_page() ) {
			$this->filter_start_checkout_event_data( $tracking_data );
		}

		if ( ! ( is_checkout() || is_product() ) ) {
			$this->filter_page_view_event_data( $tracking_data );
		}

		wp_add_inline_script(
			Config::ASSET_HANDLE_PREFIX . 'tracking',
			'
			window.snapchatAdsTrackingData = window.snapchatAdsTrackingData || {};
			window.snapchatAdsTrackingData = Object.assign( window.snapchatAdsTrackingData, ' . wp_json_encode( $tracking_data ) . ' );
			'
		);
	}

	/**
	 * Collects product pricing data for use in pixel tracking.
	 *
	 * Used during loop and single-product rendering to build a list of products
	 * and their display prices for later localization.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product Product instance being rendered.
	 */
	protected function add_product_data( WC_Product $product ): void {
		$product_id = $product->get_id();

		$this->products[ $product_id ] = array(
			'price' => wc_get_price_to_display( $product ),
		);
	}

	/**
	 * Adds `VIEW_CONTENT` tracking event to the data structure.
	 *
	 * Called when rendering a single product page, appending the price,
	 * currency, and item ID for frontend tracking. Modifies $tracking_data in-place.
	 *
	 * @since 0.1.0
	 *
	 * @param array $tracking_data Reference to tracking data array being localized.
	 */
	public function filter_view_content_event_data( &$tracking_data ): void {
		$product_id = get_the_ID();
		$product    = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$tracking_data['event_id_el_name'] = Helper::with_prefix( 'event_id' );
		$tracking_data['VIEW_CONTENT']     = array(
			'price'    => wc_get_price_to_display( $product ),
			'currency' => get_woocommerce_currency(),
			'item_ids' => array( $product_id ),
		);
	}

	/**
	 * Adds `START_CHECKOUT` tracking event to the data structure.
	 *
	 * Called when the customer lands on the checkout page. Adds
	 * cart totals, currency, item count, and product IDs to $tracking_data.
	 *
	 * @since 0.1.0
	 *
	 * @param array $tracking_data Reference to tracking data array being localized.
	 */
	public function filter_start_checkout_event_data( &$tracking_data ): void {
		if ( isset( WC()->cart ) && WC()->cart->get_cart_contents_count() > 0 ) {
			$product_ids = array_values(
				array_map(
					fn( $item ) => ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'],
					WC()->cart->get_cart()
				)
			);

			$tracking_data['START_CHECKOUT'] = array(
				'currency'     => get_woocommerce_currency(),
				'price'        => wc_format_decimal( WC()->cart->total ),
				'item_ids'     => array_map( 'strval', $product_ids ),
				'number_items' => (string) WC()->cart->get_cart_contents_count(),
			);
		}
	}

	/**
	 * Adds `PAGE_VIEW` tracking flag to the data structure.
	 *
	 * Only applies on general site pages (excluding product and checkout pages).
	 *
	 * @since 0.1.0
	 *
	 * @param array $tracking_data Reference to tracking data array being localized.
	 */
	public function filter_page_view_event_data( &$tracking_data ): void {
		$tracking_data['PAGE_VIEW'] = true;
	}

	/**
	 * Outputs a hidden input field for the Event ID on single product pages.
	 *
	 * This is used to inject a unique UUID per Add to Cart action, enabling
	 * deduplication between Pixel and CAPI events.
	 *
	 * Also injects inline JavaScript that generates a `window.crypto.randomUUID()` and
	 * assigns it to the hidden field.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render_event_id_field(): void {
		$attr = Helper::with_prefix( 'event_id' );

		printf(
			'<input type="hidden" name="%1$s" value="" />',
			esc_attr( $attr ),
		);

		wp_print_inline_script_tag(
			sprintf(
				'document.querySelector("[name=%s]").value = window.crypto.randomUUID()',
				esc_attr( $attr )
			)
		);
	}
}
