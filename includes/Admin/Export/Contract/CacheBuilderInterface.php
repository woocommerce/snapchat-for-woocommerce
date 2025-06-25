<?php
/**
 * Contract for cache builders that scan and store exportable entity IDs in batches.
 *
 * Implementers of this interface are responsible for preparing a static list of
 * entity IDs (such as products) that are eligible for export. This list is typically
 * cached in a WordPress option or transient and used by downstream services to
 * ensure consistency during batched exports.
 *
 * The scanning process must be memory-safe and asynchronous, often executed via
 * Action Scheduler using paginated jobs. This approach avoids inconsistencies
 * in export output due to catalog changes during the export run.
 *
 * @package SnapchatForWooCommerce\Admin\Export\Contract
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin\Export\Contract;

/**
 * Interface for cache builders that prepare exportable entity ID lists.
 *
 * Used to decouple the logic for scanning exportable entities from the actual
 * export job execution. By populating the export list in advance, the system
 * ensures that exports remain consistent even if new entities are added or removed
 * after the export begins.
 *
 * Implementers should:
 * - Define logic for identifying exportable entities (e.g., via meta keys).
 * - Store entity IDs in a persistent cache.
 * - Break work into small, schedulable units for large catalogs.
 *
 * @since 0.1.0
 */
interface CacheBuilderInterface {

	/**
	 * Starts the scanning and caching process.
	 *
	 * Clears any previously stored entity ID cache and queues the first
	 * batch of asynchronous scan jobs. Called before the export begins.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function build_and_cache(): void;

	/**
	 * Registers the Action Scheduler hook used to process batch jobs.
	 *
	 * This method must associate a hook name with a callback handler for
	 * scanning one page of entities. Called during plugin initialization.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register(): void;
}
