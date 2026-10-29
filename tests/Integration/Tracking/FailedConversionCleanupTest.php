<?php
/**
 * Tests for purging failed conversion send actions.
 *
 * @package SnapchatForWooCommerce\Tests\Tracking
 */

namespace SnapchatForWooCommerce\Tests\Tracking;

use ActionScheduler;
use ActionScheduler_Store;
use RuntimeException;
use WP_UnitTestCase;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Tracking\FailedConversionCleanup;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * @covers \SnapchatForWooCommerce\Tracking\FailedConversionCleanup
 */
class FailedConversionCleanupTest extends WP_UnitTestCase {

	/**
	 * Cleanup under test.
	 *
	 * @var FailedConversionCleanup
	 */
	protected $cleanup;

	/**
	 * Set up test environment.
	 *
	 * The plugin registers its own cleanup during bootstrap. Its callbacks are removed so only
	 * the instance under test handles the hooks. WP_UnitTestCase restores every hook after each test.
	 */
	public function set_up(): void {
		parent::set_up();

		global $wp_filter;

		remove_all_actions( $this->cleanup_hook() );
		foreach ( $wp_filter['action_scheduler_failed_execution']->callbacks ?? array() as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof FailedConversionCleanup ) {
					remove_action( 'action_scheduler_failed_execution', $callback['function'], $priority );
				}
			}
		}

		$this->cleanup = new FailedConversionCleanup();
		$this->cleanup->register_hooks();
	}

	/**
	 * Tear down test environment.
	 */
	public function tear_down(): void {
		foreach ( array( $this->send_hook(), $this->cleanup_hook(), 'other_plugin_hook' ) as $hook ) {
			foreach ( as_get_scheduled_actions( array( 'hook' => $hook, 'status' => '', 'per_page' => -1 ), 'ids' ) as $action_id ) {
				ActionScheduler::store()->delete_action( $action_id );
			}
		}

		parent::tear_down();
	}

	/**
	 * Test that only this plugin's failed sends older than the retention period are deleted.
	 */
	public function test_clean_deletes_only_expired_failed_sends(): void {
		$expired      = $this->create_failed_send( 31 * DAY_IN_SECONDS );
		$recent       = $this->create_failed_send( 5 * DAY_IN_SECONDS );
		$old_complete = $this->create_send();
		ActionScheduler::store()->mark_complete( $old_complete );
		$this->set_modified( $old_complete, 31 * DAY_IN_SECONDS );
		$other_hook  = $this->create_failed_action( 'other_plugin_hook', Config::PLUGIN_SLUG, 31 * DAY_IN_SECONDS );
		$other_group = $this->create_failed_action( $this->send_hook(), 'other-plugin', 31 * DAY_IN_SECONDS );

		$this->cleanup->clean();

		$this->assertFalse( $this->exists( $expired ) );
		$this->assertTrue( $this->exists( $recent ) );
		$this->assertTrue( $this->exists( $old_complete ) );
		$this->assertTrue( $this->exists( $other_hook ) );
		$this->assertTrue( $this->exists( $other_group ) );
	}

	/**
	 * Test that the cleanup follows Action Scheduler's retention period filter.
	 */
	public function test_clean_uses_action_scheduler_retention_period(): void {
		add_filter( 'action_scheduler_retention_period', fn() => WEEK_IN_SECONDS );
		$expired = $this->create_failed_send( 8 * DAY_IN_SECONDS );
		$recent  = $this->create_failed_send( 6 * DAY_IN_SECONDS );

		$this->cleanup->clean();

		$this->assertFalse( $this->exists( $expired ) );
		$this->assertTrue( $this->exists( $recent ) );
	}

	/**
	 * Test that an invalid retention period falls back to a month.
	 *
	 * @dataProvider invalid_retention_provider
	 *
	 * @param mixed $period Value returned by the retention filter.
	 */
	public function test_clean_ignores_invalid_retention_period( $period ): void {
		add_filter( 'action_scheduler_retention_period', fn() => $period );
		$recent = $this->create_failed_send( 29 * DAY_IN_SECONDS );

		$this->cleanup->clean();

		$this->assertTrue( $this->exists( $recent ) );
	}

	/**
	 * Retention periods that must be ignored.
	 *
	 * @return array
	 */
	public function invalid_retention_provider(): array {
		return array(
			'zero'       => array( 0 ),
			'negative'   => array( -1 ),
			'non-number' => array( 'forever' ),
			'array'      => array( array() ),
		);
	}

	/**
	 * Test that a run schedules the next one while failed sends remain.
	 */
	public function test_clean_reschedules_while_failed_sends_remain(): void {
		$this->create_failed_send( 5 * DAY_IN_SECONDS );

		$this->cleanup->clean();

		$runs = $this->pending_cleanups();
		$this->assertCount( 1, $runs );
		$this->assertEqualsWithDelta( time() + DAY_IN_SECONDS, reset( $runs )->get_schedule()->get_date()->getTimestamp(), 10 );
	}

	/**
	 * Test that the cleanup stops once no failed sends remain.
	 */
	public function test_clean_stops_when_no_failed_sends_remain(): void {
		$this->create_failed_send( 31 * DAY_IN_SECONDS );

		$this->cleanup->clean();

		$this->assertCount( 0, $this->pending_cleanups() );
	}

	/**
	 * Test that a full batch schedules the next run immediately.
	 */
	public function test_clean_continues_immediately_after_full_batch(): void {
		for ( $i = 0; $i < 101; $i++ ) {
			$this->create_failed_send( 31 * DAY_IN_SECONDS );
		}

		$this->cleanup->clean();

		$runs = $this->pending_cleanups();
		$this->assertCount( 1, $runs );
		$this->assertEqualsWithDelta( time(), reset( $runs )->get_schedule()->get_date()->getTimestamp(), 10 );
		$this->assertCount( 1, $this->failed_sends() );
	}

	/**
	 * Test that a send action ending Failed schedules the cleanup once.
	 */
	public function test_failed_send_schedules_cleanup(): void {
		add_action(
			$this->send_hook(),
			function () {
				throw new RuntimeException( 'Send failed.' );
			}
		);

		ActionScheduler::runner()->process_action( $this->create_send(), 'PHPUnit' );
		ActionScheduler::runner()->process_action( $this->create_send(), 'PHPUnit' );

		$runs = $this->pending_cleanups();
		$this->assertCount( 1, $runs );
		$this->assertEqualsWithDelta( time() + DAY_IN_SECONDS, reset( $runs )->get_schedule()->get_date()->getTimestamp(), 10 );
	}

	/**
	 * Test that other plugins' failed actions don't schedule the cleanup.
	 *
	 * A failed send already exists, so only the hook and group check keeps the cleanup unscheduled.
	 *
	 * @dataProvider other_action_provider
	 *
	 * @param string $hook  Action hook.
	 * @param string $group Action group.
	 */
	public function test_other_failed_action_does_not_schedule_cleanup( string $hook, string $group ): void {
		$this->create_failed_send( DAY_IN_SECONDS );
		add_action(
			$hook,
			function () {
				throw new RuntimeException( 'Other plugin failed.' );
			}
		);
		$action_id = as_schedule_single_action( time() - 1, $hook, array(), $group );

		ActionScheduler::runner()->process_action( $action_id, 'PHPUnit' );

		$this->assertCount( 0, $this->pending_cleanups() );
	}

	/**
	 * Actions that belong to other plugins.
	 *
	 * @return array
	 */
	public function other_action_provider(): array {
		return array(
			'other hook'  => array( 'other_plugin_hook', Config::PLUGIN_SLUG ),
			'other group' => array( Helper::with_prefix( 'send_conversion_event' ), 'other-plugin' ),
		);
	}

	/**
	 * Test that nothing is scheduled when there are no failed sends.
	 */
	public function test_maybe_schedule_skips_without_failed_sends(): void {
		$this->cleanup->maybe_schedule();

		$this->assertCount( 0, $this->pending_cleanups() );
	}

	/**
	 * Test that unscheduling removes a queued cleanup.
	 */
	public function test_unschedule_removes_queued_cleanup(): void {
		$this->create_failed_send( DAY_IN_SECONDS );
		$this->cleanup->maybe_schedule();

		$this->cleanup->unschedule();

		$this->assertCount( 0, $this->pending_cleanups() );
	}

	/**
	 * Creates a failed send action last modified the given number of seconds ago.
	 *
	 * @param int $age Seconds since the action was last modified.
	 * @return int Action ID.
	 */
	private function create_failed_send( int $age ): int {
		return $this->create_failed_action( $this->send_hook(), Config::PLUGIN_SLUG, $age );
	}

	/**
	 * Creates a pending send action carrying a visitor IP address, as a real Purchase send does.
	 *
	 * @return int Action ID.
	 */
	private function create_send(): int {
		return as_schedule_single_action(
			time() - 1,
			$this->send_hook(),
			array(
				'event_payload' => array( 'user_data' => array( 'client_ip_address' => '203.0.113.7' ) ),
				'args'          => array( 'order_id' => 1 ),
			),
			Config::PLUGIN_SLUG
		);
	}

	/**
	 * Creates a failed action last modified the given number of seconds ago.
	 *
	 * @param string $hook  Action hook.
	 * @param string $group Action group.
	 * @param int    $age   Seconds since the action was last modified.
	 * @return int Action ID.
	 */
	private function create_failed_action( string $hook, string $group, int $age ): int {
		$action_id = as_schedule_single_action( time() - 1, $hook, array( 'id' => wp_generate_uuid4() ), $group );
		ActionScheduler::store()->mark_failure( $action_id );
		$this->set_modified( $action_id, $age );

		return $action_id;
	}

	/**
	 * Backdates when an action was last modified.
	 *
	 * @param int $action_id Action ID.
	 * @param int $age       Seconds since the action was last modified.
	 */
	private function set_modified( int $action_id, int $age ): void {
		global $wpdb;

		$date = gmdate( 'Y-m-d H:i:s', time() - $age );
		$wpdb->update(
			$wpdb->actionscheduler_actions,
			array(
				'last_attempt_gmt'   => $date,
				'last_attempt_local' => $date,
			),
			array( 'action_id' => $action_id )
		);
	}

	/**
	 * Determines whether an action still exists.
	 *
	 * @param int $action_id Action ID.
	 * @return bool
	 */
	private function exists( int $action_id ): bool {
		return '' !== ActionScheduler::store()->fetch_action( $action_id )->get_hook();
	}

	/**
	 * Returns pending cleanup actions.
	 *
	 * @return \ActionScheduler_Action[]
	 */
	private function pending_cleanups(): array {
		return as_get_scheduled_actions(
			array(
				'hook'     => $this->cleanup_hook(),
				'group'    => Config::PLUGIN_SLUG,
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1,
			)
		);
	}

	/**
	 * Returns IDs of failed send actions.
	 *
	 * @return int[]
	 */
	private function failed_sends(): array {
		return as_get_scheduled_actions(
			array(
				'hook'     => $this->send_hook(),
				'group'    => Config::PLUGIN_SLUG,
				'status'   => ActionScheduler_Store::STATUS_FAILED,
				'per_page' => -1,
			),
			'ids'
		);
	}

	/**
	 * Returns the send action hook.
	 *
	 * @return string
	 */
	private function send_hook(): string {
		return Helper::with_prefix( 'send_conversion_event' );
	}

	/**
	 * Returns the cleanup action hook.
	 *
	 * @return string
	 */
	private function cleanup_hook(): string {
		return Helper::with_prefix( FailedConversionCleanup::ACTION_HOOK );
	}
}
