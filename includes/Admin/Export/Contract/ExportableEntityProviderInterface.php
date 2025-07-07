<?php
/**
 * Contract for exportable entity providers.
 *
 * This interface defines the data source for batch exports — typically
 * products, categories, or any custom post types. Implementations must
 * return exportable entity IDs in paginated chunks and resolve those IDs
 * into full entity objects.
 *
 * Designed to support batch-safe, large-scale exports using offset/limit
 * logic to avoid memory overhead.
 *
 * @package SnapchatForWooCommerce\Admin\Export\Contract
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\Contract;

/**
 * Interface for providing exportable entities in paginated batches.
 *
 * Implementations of this interface must:
 * - Return a total count of entities to be exported.
 * - Provide a paginated slice of entity IDs (offset + limit).
 * - Resolve those IDs into entity objects (e.g., WC_Product, WP_Term).
 *
 * This design supports large data sets without requiring the full list
 * of IDs to be loaded into memory.
 *
 * @since 0.1.0
 */
interface ExportableEntityProviderInterface {

	/**
	 * Returns the total number of entities that match export criteria.
	 *
	 * This is used to calculate how many batches will be needed.
	 *
	 * @since 0.1.0
	 *
	 * @return int Number of exportable entities.
	 */
	public function get_total(): int;

	/**
	 * Returns a page of exportable entity IDs for a given offset and limit.
	 *
	 * Each batch export job will call this method to get the IDs it
	 * should process during that specific batch run.
	 *
	 * @since 0.1.0
	 *
	 * @param int $offset Zero-based offset into the full exportable list.
	 * @param int $limit Maximum number of entity IDs to return.
	 * @return array<int> Ordered list of entity IDs to process in this batch.
	 */
	public function get_ids( int $offset, int $limit ): array;

	/**
	 * Returns resolved entity objects for the given list of IDs.
	 *
	 * These entity objects (e.g., WC_Product, WP_Term) will be passed
	 * to the row builder to convert into exportable data.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int> $ids List of entity IDs.
	 * @return array<int, mixed> List of resolved entity objects.
	 */
	public function get_entities( array $ids ): array;
}
