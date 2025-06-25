<?php
/**
 * Constants used across the product catalog export feature.
 *
 * This file defines constants that encapsulate keys, option names,
 * and flags specific to the catalog export functionality within the
 * Snapchat for WooCommerce plugin.
 *
 * These constants are used across services that control export eligibility,
 * scheduling, row formatting, and caching behavior. Centralizing these
 * values helps ensure consistency and ease of future maintenance.
 *
 * @package SnapchatForWooCommerce\Export
 */

namespace SnapchatForWooCommerce\Export;

/**
 * Defines shared constants used by the catalog export subsystem.
 *
 * This class is not intended to be instantiated. It acts as a namespace for
 * constants related to product export functionality — including option keys,
 * metadata flags, and system-specific values used to determine product eligibility
 * for inclusion in the export process.
 *
 * @since 0.1.0
 */
final class ExportConstants {

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
}
