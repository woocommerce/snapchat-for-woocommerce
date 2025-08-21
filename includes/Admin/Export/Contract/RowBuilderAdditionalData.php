<?php
/**
 * Defines a generic extension point for enriching export rows with additional data.
 *
 * Implementations of this interface can provide extra attributes beyond the
 * core entity fields (e.g., product category, brand, or MPN). Each provider
 * contributes a small associative array that is merged into the final export row
 * by a row builder such as {@see ProductRowBuilder}.
 *
 * This generic contract allows extension of multiple entity types, keeping
 * row builders clean and enabling optional metadata to be supplied by
 * dedicated classes.
 *
 * @package SnapchatForWooCommerce\Admin\Export\Contract
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\Contract;

/**
 * Contract for providers that contribute additional export row fields.
 *
 * Implementations should return an associative array of scalar values keyed
 * by the target feed's expected field names. If no data is available, return
 * an empty array. Entity-specific sub-interfaces may provide stronger typing
 * (e.g., {@see ProductRowBuilderAdditionalData}).
 *
 * @since 0.1.0
 */
interface RowBuilderAdditionalData {

	/**
	 * Provide additional data for an export row.
	 *
	 * Implementations are expected to extract relevant metadata from the entity
	 * (e.g., taxonomy, attributes, or custom meta) and return it in a format
	 * suitable for merging into the export row.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $entity The exportable entity (e.g., WC_Product, WC_Order).
	 * @return array<string,scalar> Associative data to merge into the row.
	 */
	public function get_additional_data( $entity ): array;
}
