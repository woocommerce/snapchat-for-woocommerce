<?php
/**
 * Contract for converting entities into exportable row data.
 *
 * Implementations of this interface transform an entity object
 * (e.g., product, category, order) into a flat array suitable for
 * CSV or other structured formats.
 *
 * @package SnapchatForWooCommerce\Admin\Export\Contract
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\Contract;

/**
 * Interface for building a row from an exportable entity.
 *
 * Implementations convert an entity object into an array of scalar values,
 * with each key representing a column in the final output (CSV, JSON, etc).
 *
 * @since 0.1.0
 */
interface ExportRowBuilderInterface {

	/**
	 * Builds an exportable row from the given entity.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $entity The entity object (e.g., WC_Product, WP_Term).
	 * @return array<string, scalar>|null Associative array for one row, or null to skip.
	 */
	public function build_row( $entity ): ?array;
}
