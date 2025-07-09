<?php
/**
 * Adds Snapchat tab and exportable checkbox to WooCommerce product editor.
 *
 * This integration allows store admins to explicitly include or exclude
 * individual products from Snapchat’s product catalog export.
 *
 * @package SnapchatForWooCommerce\Admin\ProductMeta
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\ProductMeta;

use SnapchatForWooCommerce\Utils\Helper;

/**
 * Registers the "Snapchat" tab and checkbox in the WooCommerce product editor.
 *
 * This allows merchants to designate which products should be included
 * in the Snapchat product catalog via a simple checkbox UI.
 *
 * @since 0.1.0
 */
class ProductMetaFields {

	/**
	 * Meta key used to determine if a product is eligible for export.
	 *
	 * When this custom post meta is set to true (or a truthy value), the corresponding
	 * product is considered exportable and will be included in catalog generation logic.
	 * If this flag is missing or set to false, the product will be skipped.
	 *
	 * Used by:
	 * - {@see ProductEntityProvider} to filter exportable product IDs.
	 * - Admin UI or automation logic to toggle export eligibility.
	 *
	 * @since 0.1.0
	 */
	public const CATALOG_ITEM = 'product_catalog_item';

	/**
	 * Registers all WooCommerce hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_hooks(): void {
		add_action(
			'current_screen',
			function ( $screen ) {
				if ( 'product' !== $screen->post_type || 'post' !== $screen->base ) {
					return;
				}

				add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tab' ) );
				add_action( 'woocommerce_product_data_panels', array( $this, 'render_panel' ) );
				add_action( 'woocommerce_process_product_meta', array( $this, 'save_meta' ) );
			}
		);
	}

	/**
	 * Adds the Snapchat tab to the product data panel.
	 *
	 * @since 0.1.0
	 *
	 * @param array $tabs Existing WooCommerce product data tabs.
	 * @return array Modified tabs.
	 */
	public function add_tab( array $tabs ): array {
		$tabs['snapchat'] = array(
			'label'    => __( 'Snapchat', 'snapchat-for-woocommerce' ),
			'target'   => 'snapchat_product_data',
			'class'    => array(),
			'priority' => 90,
		);
		return $tabs;
	}

	/**
	 * Renders the Snapchat product data panel.
	 *
	 * Displays a checkbox allowing the merchant to mark the product as eligible
	 * for inclusion in the Snapchat product catalog.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render_panel(): void {
		global $post;

		$meta_key = Helper::with_prefix( self::CATALOG_ITEM );
		$value    = get_post_meta( $post->ID, $meta_key, true );

		if ( '' === $value ) {
			$value = '1';
		}

		?>
		<div id="snapchat_product_data" class="panel woocommerce_options_panel hidden">
			<p class="form-field">
				<label for="<?php echo esc_attr( $meta_key ); ?>">
					<?php esc_html_e( 'Catalog Item', 'snapchat-for-woocommerce' ); ?>
				</label>
				<input type="checkbox"
					name="<?php echo esc_attr( $meta_key ); ?>"
					id="<?php echo esc_attr( $meta_key ); ?>"
					value="1" <?php checked( $value, '1' ); ?> />
				<span class="description">
					<?php esc_html_e( "Include this product in Snapchat's product catalog.", 'snapchat-for-woocommerce' ); ?>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Saves the Snapchat exportable flag when the product is saved.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The ID of the current product.
	 * @return void
	 */
	public function save_meta( int $post_id ): void {
		$meta_key = Helper::with_prefix( self::CATALOG_ITEM );

		// Nonce verification done in the Woo Core parent method.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$enabled = isset( $_POST[ $meta_key ] ) && '1' === $_POST[ $meta_key ];

		update_post_meta( $post_id, $meta_key, $enabled ? '1' : '0' );
	}
}
