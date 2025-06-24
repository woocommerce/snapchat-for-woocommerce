<?php
namespace SnapchatForWooCommerce\Export;

/**
 * Constants used across the product catalog export feature.
 *
 * Defines meta keys, option names, and other values relevant
 * only to the catalog export logic.
 *
 * @since 0.1.0
 */
final class ExportConstants {

	/**
	 * Meta key used to determine if a product is eligible for export.
	 *
	 * When this meta key is set to true, the product will be included
	 * in the catalog export.
	 *
	 * @since 0.1.0
	 */
	public const CATALOG_ITEM = 'product_catalog_item';
}
