<?php
/**
 * Unit tests for CampaignStatusService.
 *
 * Covers `has_campaigns()` reading only the cache and scheduling a background
 * check on a miss or an ad account mismatch, `refresh_cache()` checking the
 * live Ad Partner endpoint and caching the result with its ad account ID, and
 * the cache being busted by the
 * `onboarding_complete`/`snapchat_disconnected` hooks, with queued checks
 * cancelled on disconnect.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Campaign
 */

namespace SnapchatForWooCommerce\Tests\Unit\Campaign;

use WP_Error;
use WP_REST_Response;
use WP_UnitTestCase;
use SnapchatForWooCommerce\API\AdPartner\AdPartnerApi;
use SnapchatForWooCommerce\API\AdPartner\CampaignApi;
use SnapchatForWooCommerce\Campaign\CampaignStatusService;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * @covers \SnapchatForWooCommerce\Campaign\CampaignStatusService
 */
class CampaignStatusServiceTest extends WP_UnitTestCase {

	/**
	 * Mocked campaign API, exposed through the mocked Ad Partner API.
	 *
	 * @var CampaignApi&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $campaign_api;

	/**
	 * Service under test.
	 *
	 * @var CampaignStatusService
	 */
	private CampaignStatusService $service;

	/**
	 * Reset the transient, option, and scheduled actions the tests mutate.
	 */
	public function set_up(): void {
		parent::set_up();

		Transients::delete( TransientDefaults::HAS_CAMPAIGNS );
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'abc-123' );
		as_unschedule_all_actions( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) );

		$this->campaign_api       = $this->createMock( CampaignApi::class );
		$ad_partner_api           = $this->createMock( AdPartnerApi::class );
		$ad_partner_api->campaign = $this->campaign_api;

		$this->service = new CampaignStatusService( $ad_partner_api );
	}

	public function tear_down(): void {
		Transients::delete( TransientDefaults::HAS_CAMPAIGNS );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		as_unschedule_all_actions( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) );
		parent::tear_down();
	}

	/**
	 * Test: a cache miss returns `true`, schedules a background check, and
	 * never calls the API inline.
	 */
	public function test_cache_miss_returns_true_and_schedules_refresh(): void {
		$this->campaign_api->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertTrue( $this->service->has_campaigns() );
		$this->assertTrue( as_has_scheduled_action( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) ) );
	}

	/**
	 * Test: repeated cache misses only schedule one pending background check.
	 */
	public function test_cache_miss_schedules_refresh_only_once(): void {
		$this->service->has_campaigns();
		$this->service->has_campaigns();

		$this->assertCount(
			1,
			as_get_scheduled_actions(
				array(
					'hook'   => Helper::with_prefix( CampaignStatusService::ACTION_HOOK ),
					'status' => \ActionScheduler_Store::STATUS_PENDING,
				),
				'ids'
			)
		);
	}

	/**
	 * Test: a cached result is returned without scheduling a background check.
	 */
	public function test_cache_hit_returns_cached_value_without_scheduling(): void {
		Transients::set( TransientDefaults::HAS_CAMPAIGNS, $this->cached_result( 'abc-123', false ) );
		$this->assertFalse( $this->service->has_campaigns() );

		Transients::set( TransientDefaults::HAS_CAMPAIGNS, $this->cached_result( 'abc-123', true ) );
		$this->assertTrue( $this->service->has_campaigns() );

		$this->assertFalse( as_has_scheduled_action( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) ) );
	}

	/**
	 * Test: a cached result for a different ad account is treated as a cache
	 * miss, so a stale result is never used after the account changes.
	 */
	public function test_cached_result_for_other_ad_account_is_ignored(): void {
		Transients::set( TransientDefaults::HAS_CAMPAIGNS, $this->cached_result( 'old-account', false ) );

		$this->assertTrue( $this->service->has_campaigns() );
		$this->assertTrue( as_has_scheduled_action( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) ) );
	}

	/**
	 * Test: a cached value in an unexpected format is treated as a cache miss.
	 */
	public function test_cached_result_in_unexpected_format_is_ignored(): void {
		Transients::set( TransientDefaults::HAS_CAMPAIGNS, '0' );

		$this->assertTrue( $this->service->has_campaigns() );
		$this->assertTrue( as_has_scheduled_action( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) ) );
	}

	/**
	 * Test: the refresh is skipped without calling the API when no ad account is connected.
	 */
	public function test_refresh_skips_when_no_ad_account(): void {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, '' );

		$this->campaign_api->expects( $this->never() )->method( 'get_campaigns' );

		$this->service->refresh_cache();

		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
	}

	/**
	 * Test: the refresh requests campaigns for the stored ad account.
	 */
	public function test_refresh_requests_campaigns_for_stored_ad_account(): void {
		$this->campaign_api->expects( $this->once() )
			->method( 'get_campaigns' )
			->with( 'abc-123' )
			->willReturn( new WP_REST_Response( array( 'campaigns' => array() ), 200 ) );

		$this->service->refresh_cache();
	}

	/**
	 * Test: the refresh caches a positive result for the checked ad account when
	 * the response contains at least one campaign.
	 */
	public function test_refresh_caches_true_when_campaigns_exist(): void {
		$this->campaign_api->method( 'get_campaigns' )->willReturn(
			new WP_REST_Response(
				array(
					'campaigns' => array(
						array(
							'sub_request_status' => 'SUCCESS',
							'campaign'           => array(
								'id'     => 'campaign-1',
								'status' => 'PAUSED',
							),
						),
					),
				),
				200
			)
		);

		$this->service->refresh_cache();

		$this->assertSame( $this->cached_result( 'abc-123', true ), Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
		$this->assertTrue( $this->service->has_campaigns() );
	}

	/**
	 * Test: the refresh caches a negative result for the checked ad account when
	 * the response contains no campaigns.
	 */
	public function test_refresh_caches_false_when_no_campaigns(): void {
		$this->campaign_api->method( 'get_campaigns' )->willReturn(
			new WP_REST_Response( array( 'campaigns' => array() ), 200 )
		);

		$this->service->refresh_cache();

		$this->assertSame( $this->cached_result( 'abc-123', false ), Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
		$this->assertFalse( $this->service->has_campaigns() );
	}

	/**
	 * Test: when the ad account changes while a check is in flight, the result
	 * stays tagged with the account that was checked and isn't used for the
	 * new one.
	 */
	public function test_refresh_result_is_not_used_after_ad_account_changes_mid_check(): void {
		$this->campaign_api->method( 'get_campaigns' )->willReturnCallback(
			function () {
				Options::set( OptionDefaults::AD_ACCOUNT_ID, 'new-account' );
				return new WP_REST_Response( array( 'campaigns' => array() ), 200 );
			}
		);

		$this->service->refresh_cache();

		$this->assertSame( $this->cached_result( 'abc-123', false ), Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
		$this->assertTrue( $this->service->has_campaigns() );
	}

	/**
	 * Test: a failed live call is not cached, so the next cache miss retries.
	 */
	public function test_refresh_does_not_cache_wp_error(): void {
		$this->campaign_api->method( 'get_campaigns' )->willReturn(
			new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' )
		);

		$this->service->refresh_cache();

		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
	}

	/**
	 * Test: a response without a `campaigns` array is not cached as "no campaigns".
	 */
	public function test_refresh_does_not_cache_when_campaigns_missing(): void {
		$this->campaign_api->method( 'get_campaigns' )->willReturn(
			new WP_REST_Response( array( 'request_status' => 'ERROR' ), 200 )
		);

		$this->service->refresh_cache();

		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
		$this->assertTrue( $this->service->has_campaigns() );
	}

	/**
	 * Test: `register_hooks()` wires `refresh_cache()` to the Action Scheduler hook.
	 */
	public function test_register_hooks_wires_refresh_to_action_hook(): void {
		$this->campaign_api->expects( $this->once() )
			->method( 'get_campaigns' )
			->willReturn( new WP_REST_Response( array( 'campaigns' => array() ), 200 ) );

		$this->service->register_hooks();

		do_action( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) );

		$this->assertSame( $this->cached_result( 'abc-123', false ), Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
	}

	/**
	 * Test: `register_hooks()` wires `clear_cache()` to both connection-change hooks.
	 */
	public function test_register_hooks_busts_cache_on_onboarding_complete_and_disconnect(): void {
		Transients::set( TransientDefaults::HAS_CAMPAIGNS, $this->cached_result( 'abc-123', true ) );

		$this->service->register_hooks();

		do_action( Helper::with_prefix( 'onboarding_complete' ) );
		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );

		Transients::set( TransientDefaults::HAS_CAMPAIGNS, $this->cached_result( 'abc-123', true ) );

		do_action( Helper::with_prefix( 'snapchat_disconnected' ) );
		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
	}

	/**
	 * Test: disconnecting cancels a queued background check.
	 */
	public function test_register_hooks_unschedules_refresh_on_disconnect(): void {
		$this->service->register_hooks();

		$this->service->schedule_refresh();
		$this->assertTrue( as_has_scheduled_action( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) ) );

		do_action( Helper::with_prefix( 'snapchat_disconnected' ) );

		$this->assertFalse( as_has_scheduled_action( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) ) );
	}

	/**
	 * Test: reconnecting keeps a queued background check, since it reads the
	 * current ad account when it runs.
	 */
	public function test_onboarding_complete_keeps_queued_refresh(): void {
		$this->service->register_hooks();

		$this->service->schedule_refresh();

		do_action( Helper::with_prefix( 'onboarding_complete' ) );

		$this->assertTrue( as_has_scheduled_action( Helper::with_prefix( CampaignStatusService::ACTION_HOOK ) ) );
	}

	/**
	 * Builds a cached campaign result in the format `refresh_cache()` stores.
	 *
	 * @param string $ad_account_id Ad account the result was checked for.
	 * @param bool   $has_campaigns Whether the ad account has campaigns.
	 * @return array
	 */
	private function cached_result( string $ad_account_id, bool $has_campaigns ): array {
		return array(
			'ad_account_id' => $ad_account_id,
			'has_campaigns' => $has_campaigns,
		);
	}
}
