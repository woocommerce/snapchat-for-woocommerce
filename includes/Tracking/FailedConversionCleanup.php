<?php
/**
 * Deletes failed conversion send actions once they pass the Action Scheduler retention period.
 *
 * @package SnapchatForWooCommerce\Tracking
 */

namespace SnapchatForWooCommerce\Tracking;

use ActionScheduler;
use ActionScheduler_Store;
use Exception;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * Purges this plugin's failed `send_conversion_event` actions after the retention period.
 *
 * Each send action stores the event payload as its args, including the visitor's plain-text
 * IP address and user agent. Action Scheduler's cleaner only purges complete and canceled
 * actions, so without this a failed send, and every retry of it, would keep that data
 * indefinitely. Failed rows are kept for the same period Action Scheduler keeps completed
 * ones, so merchants and support can still read why a send failed.
 *
 * The cleanup only runs while failed sends exist. A failed send schedules it, and each run
 * schedules the next one until no failed sends remain.
 *
 * @since x.x.x
 */
final class FailedConversionCleanup {

	/**
	 * Hook suffix of the cleanup action.
	 */
	public const ACTION_HOOK = 'clean_failed_conversions';

	/**
	 * Maximum number of failed actions deleted per run.
	 */
	private const BATCH_SIZE = 100;

	/**
	 * Registers the cleanup action and the trigger that schedules it.
	 *
	 * Registered regardless of whether conversion tracking is enabled, so failed sends left
	 * behind after tracking is switched off are still purged.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( Helper::with_prefix( self::ACTION_HOOK ), array( $this, 'clean' ) );
		add_action( 'action_scheduler_failed_execution', array( $this, 'handle_failed_action' ), 10, 1 );
	}

	/**
	 * Schedules the cleanup when one of this plugin's send actions fails.
	 *
	 * Hook callback for `action_scheduler_failed_execution`.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $action_id ID of the action that failed.
	 * @return void
	 */
	public function handle_failed_action( $action_id ): void {
		if ( ! is_numeric( $action_id ) || ! class_exists( ActionScheduler::class ) ) {
			return;
		}

		$action = ActionScheduler::store()->fetch_action( (int) $action_id );

		if ( $this->get_send_hook() !== $action->get_hook() || Config::PLUGIN_SLUG !== $action->get_group() ) {
			return;
		}

		$this->maybe_schedule();
	}

	/**
	 * Schedules the cleanup if failed sends exist and no cleanup is already queued.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function maybe_schedule(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		if ( as_has_scheduled_action( Helper::with_prefix( self::ACTION_HOOK ), null, Config::PLUGIN_SLUG ) ) {
			return;
		}

		if ( ! $this->get_failed_sends() ) {
			return;
		}

		$this->schedule( time() + DAY_IN_SECONDS );
	}

	/**
	 * Removes any queued cleanup.
	 *
	 * Called on plugin deactivation, when the cleanup callback is no longer registered.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function unschedule(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( Helper::with_prefix( self::ACTION_HOOK ), null, Config::PLUGIN_SLUG );
		}
	}

	/**
	 * Deletes failed sends older than the retention period and schedules the next run.
	 *
	 * Hook callback for the cleanup action.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function clean(): void {
		$expired = $this->get_failed_sends( time() - $this->get_retention_period(), self::BATCH_SIZE );

		foreach ( $expired as $action_id ) {
			try {
				ActionScheduler::store()->delete_action( $action_id );
			} catch ( Exception $e ) {
				// The action was already deleted; nothing left to purge.
				continue;
			}
		}

		// This action is still running, so schedule the next run directly: maybe_schedule() would see it and skip.
		if ( count( $expired ) >= self::BATCH_SIZE ) {
			$this->schedule( time() );
		} elseif ( $this->get_failed_sends() ) {
			$this->schedule( time() + DAY_IN_SECONDS );
		}
	}

	/**
	 * Returns IDs of this plugin's failed send actions.
	 *
	 * @param int|null $modified_before Only return actions last modified at or before this timestamp.
	 * @param int      $limit           Maximum number of IDs to return.
	 * @return int[]
	 */
	private function get_failed_sends( ?int $modified_before = null, int $limit = 1 ): array {
		$query = array(
			'hook'     => $this->get_send_hook(),
			'group'    => Config::PLUGIN_SLUG,
			'status'   => ActionScheduler_Store::STATUS_FAILED,
			'per_page' => $limit,
			'orderby'  => 'none',
		);

		if ( null !== $modified_before ) {
			$query['modified']         = $modified_before;
			$query['modified_compare'] = '<=';
		}

		return array_map( 'intval', as_get_scheduled_actions( $query, 'ids' ) );
	}

	/**
	 * Returns how long failed sends are kept, matching Action Scheduler's retention for completed actions.
	 *
	 * @return int Retention period in seconds.
	 */
	private function get_retention_period(): int {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Action Scheduler's own filter, applied so both retention periods stay in step.
		$period = apply_filters( 'action_scheduler_retention_period', MONTH_IN_SECONDS );

		return is_numeric( $period ) && $period > 0 ? (int) $period : MONTH_IN_SECONDS;
	}

	/**
	 * Schedules a single cleanup run.
	 *
	 * @param int $timestamp When to run.
	 * @return void
	 */
	private function schedule( int $timestamp ): void {
		as_schedule_single_action( $timestamp, Helper::with_prefix( self::ACTION_HOOK ), array(), Config::PLUGIN_SLUG );
	}

	/**
	 * Returns the hook of the conversion send action.
	 *
	 * @return string
	 */
	private function get_send_hook(): string {
		return Helper::with_prefix( 'send_conversion_event' );
	}
}
