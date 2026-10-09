<?php
/**
 * Unit tests for CampaignApi.
 *
 * Covers `has_campaigns()` reading only the cache and scheduling a background
 * check on a miss, `refresh_cache()` checking the live Ad Partner endpoint and
 * caching the result, and the cache being busted by the
 * `onboarding_complete`/`snapchat_disconnected` hooks.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\API\AdPartner
 */

namespace SnapchatForWooCommerce\Tests\Unit\API\AdPartner;

use WP_Error;
use WP_REST_Response;
use WP_UnitTestCase;
use SnapchatForWooCommerce\API\AdPartner\CampaignApi;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Helper;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * @covers \SnapchatForWooCommerce\API\AdPartner\CampaignApi
 */
class CampaignApiTest extends WP_UnitTestCase {

	/**
	 * Reset the transient, option, and scheduled actions the tests mutate.
	 */
	public function set_up(): void {
		parent::set_up();

		Transients::delete( TransientDefaults::HAS_CAMPAIGNS );
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'abc-123' );
		as_unschedule_all_actions( Helper::with_prefix( CampaignApi::ACTION_HOOK ) );
	}

	public function tear_down(): void {
		Transients::delete( TransientDefaults::HAS_CAMPAIGNS );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		as_unschedule_all_actions( Helper::with_prefix( CampaignApi::ACTION_HOOK ) );
		parent::tear_down();
	}

	/**
	 * Test: a cache miss returns `true`, schedules a background check, and
	 * never calls WCS inline.
	 */
	public function test_cache_miss_returns_true_and_schedules_refresh(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->never() )->method( 'proxy_get' );

		$api = new CampaignApi( $wcs );

		$this->assertTrue( $api->has_campaigns() );
		$this->assertTrue( as_has_scheduled_action( Helper::with_prefix( CampaignApi::ACTION_HOOK ) ) );
	}

	/**
	 * Test: repeated cache misses only schedule one pending background check.
	 */
	public function test_cache_miss_schedules_refresh_only_once(): void {
		$api = new CampaignApi( $this->createMock( WcsClient::class ) );

		$api->has_campaigns();
		$api->has_campaigns();

		$this->assertCount(
			1,
			as_get_scheduled_actions(
				array(
					'hook'   => Helper::with_prefix( CampaignApi::ACTION_HOOK ),
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
		$api = new CampaignApi( $this->createMock( WcsClient::class ) );

		Transients::set( TransientDefaults::HAS_CAMPAIGNS, '0' );
		$this->assertFalse( $api->has_campaigns() );

		Transients::set( TransientDefaults::HAS_CAMPAIGNS, '1' );
		$this->assertTrue( $api->has_campaigns() );

		$this->assertFalse( as_has_scheduled_action( Helper::with_prefix( CampaignApi::ACTION_HOOK ) ) );
	}

	/**
	 * Test: the refresh is skipped without hitting WCS when no ad account is connected.
	 */
	public function test_refresh_skips_when_no_ad_account(): void {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, '' );

		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->never() )->method( 'proxy_get' );

		( new CampaignApi( $wcs ) )->refresh_cache();

		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
	}

	/**
	 * Test: the refresh proxies GET to `/v1/adaccounts/{id}/campaigns` with the
	 * stored ad account ID URL-encoded.
	 */
	public function test_refresh_proxies_to_correct_endpoint(): void {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'abc 123' );

		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->once() )
			->method( 'proxy_get' )
			->with( '/v1/adaccounts/abc%20123/campaigns' )
			->willReturn( new WP_REST_Response( array( 'campaigns' => array() ), 200 ) );

		( new CampaignApi( $wcs ) )->refresh_cache();
	}

	/**
	 * Test: the refresh caches `'1'` when the response contains at least one campaign.
	 */
	public function test_refresh_caches_true_when_campaigns_exist(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->method( 'proxy_get' )->willReturn(
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

		$api = new CampaignApi( $wcs );
		$api->refresh_cache();

		$this->assertSame( '1', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
		$this->assertTrue( $api->has_campaigns() );
	}

	/**
	 * Test: the refresh caches `'0'` when the response contains no campaigns.
	 */
	public function test_refresh_caches_false_when_no_campaigns(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->method( 'proxy_get' )->willReturn(
			new WP_REST_Response( array( 'campaigns' => array() ), 200 )
		);

		$api = new CampaignApi( $wcs );
		$api->refresh_cache();

		$this->assertSame( '0', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
		$this->assertFalse( $api->has_campaigns() );
	}

	/**
	 * Test: a failed live call is not cached, so the next cache miss retries.
	 */
	public function test_refresh_does_not_cache_wp_error(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->method( 'proxy_get' )->willReturn(
			new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' )
		);

		( new CampaignApi( $wcs ) )->refresh_cache();

		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
	}

	/**
	 * Test: a response without a `campaigns` array is not cached as "no campaigns".
	 */
	public function test_refresh_does_not_cache_when_campaigns_missing(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->method( 'proxy_get' )->willReturn(
			new WP_REST_Response( array( 'request_status' => 'ERROR' ), 200 )
		);

		$api = new CampaignApi( $wcs );
		$api->refresh_cache();

		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
		$this->assertTrue( $api->has_campaigns() );
	}

	/**
	 * Test: `register_hooks()` wires `refresh_cache()` to the Action Scheduler hook.
	 */
	public function test_register_hooks_wires_refresh_to_action_hook(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->once() )
			->method( 'proxy_get' )
			->willReturn( new WP_REST_Response( array( 'campaigns' => array() ), 200 ) );

		$api = new CampaignApi( $wcs );
		$api->register_hooks();

		do_action( Helper::with_prefix( CampaignApi::ACTION_HOOK ) );

		$this->assertSame( '0', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
	}

	/**
	 * Test: `register_hooks()` wires `clear_cache()` to both connection-change hooks.
	 */
	public function test_register_hooks_busts_cache_on_onboarding_complete_and_disconnect(): void {
		Transients::set( TransientDefaults::HAS_CAMPAIGNS, '1' );

		$wcs = $this->createMock( WcsClient::class );
		$api = new CampaignApi( $wcs );
		$api->register_hooks();

		do_action( Helper::with_prefix( 'onboarding_complete' ) );
		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );

		Transients::set( TransientDefaults::HAS_CAMPAIGNS, '1' );

		do_action( Helper::with_prefix( 'snapchat_disconnected' ) );
		$this->assertSame( '', Transients::get( TransientDefaults::HAS_CAMPAIGNS ) );
	}
}
