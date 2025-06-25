<?php
namespace SnapchatForWooCommerce\Export\Contract;

/**
 * Contract for cache builders that scan and store exportable entity IDs in batches.
 *
 * @since 0.1.0
 */
interface CacheBuilderInterface {

	/**
	 * Starts the scanning and caching process (clears old data and queues batch jobs).
	 *
	 * @return void
	 */
	public function build_and_cache(): void;

	/**
	 * Registers the Action Scheduler hook for batch scanning.
	 *
	 * @return void
	 */
	public function register(): void;
}
