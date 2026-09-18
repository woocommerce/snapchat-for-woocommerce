<?php
/**
 * Unit tests for CampaignApi.
 *
 * Covers the SNAPWOO-105 behaviour: `has_active_campaigns()` checks the live
 * Ad Partner endpoint, caches the result, fails open (`true`) on error, and
 * has its cache busted by the `onboarding_complete`/`snapchat_disconnected`
 * hooks.
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
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;

/**
 * @covers \SnapchatForWooCommerce\API\AdPartner\CampaignApi
 */
class CampaignApiTest extends WP_UnitTestCase {

	/**
	 * Reset the transient the tests mutate.
	 */
	public function set_up(): void {
		parent::set_up();

		Transients::delete( TransientDefaults::CAMPAIGN_HAS_ACTIVE );
	}

	public function tear_down(): void {
		Transients::delete( TransientDefaults::CAMPAIGN_HAS_ACTIVE );
		parent::tear_down();
	}

	/**
	 * Test: an empty ad account ID short-circuits to `true` without hitting WCS.
	 */
	public function test_returns_true_for_empty_ad_account_id_without_hitting_wcs(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->never() )->method( 'proxy_get' );

		$api = new CampaignApi( $wcs );

		$this->assertTrue( $api->has_active_campaigns( '' ) );
	}

	/**
	 * Test: proxies GET to `/v1/adaccounts/{id}/campaigns` with the ID URL-encoded.
	 */
	public function test_proxies_to_correct_endpoint(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->once() )
			->method( 'proxy_get' )
			->with( '/v1/adaccounts/abc-123/campaigns' )
			->willReturn( new WP_REST_Response( array( 'campaigns' => array() ), 200 ) );

		$api = new CampaignApi( $wcs );
		$api->has_active_campaigns( 'abc-123' );
	}

	/**
	 * Test: returns `true` when the response contains at least one campaign.
	 */
	public function test_returns_true_when_campaigns_exist(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->method( 'proxy_get' )->willReturn(
			new WP_REST_Response(
				array(
					'campaigns' => array(
						array(
							'sub_request_status' => 'SUCCESS',
							'campaign'            => array(
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

		$this->assertTrue( $api->has_active_campaigns( 'abc-123' ) );
	}

	/**
	 * Test: returns `false` when the response has no campaigns.
	 */
	public function test_returns_false_when_no_campaigns_exist(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->method( 'proxy_get' )->willReturn(
			new WP_REST_Response( array( 'campaigns' => array() ), 200 )
		);

		$api = new CampaignApi( $wcs );

		$this->assertFalse( $api->has_active_campaigns( 'abc-123' ) );
	}

	/**
	 * Test: fails open to `true` when the live call returns a WP_Error, and
	 * does not cache the failure (so the next call retries the live check).
	 */
	public function test_fails_open_to_true_on_wp_error_and_does_not_cache(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->exactly( 2 ) )
			->method( 'proxy_get' )
			->willReturn( new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) );

		$api = new CampaignApi( $wcs );

		$this->assertTrue( $api->has_active_campaigns( 'abc-123' ) );
		$this->assertTrue( $api->has_active_campaigns( 'abc-123' ) );
	}

	/**
	 * Test: the live check is only performed once; the second call reuses the
	 * cached `false` result instead of hitting WCS again.
	 */
	public function test_caches_false_result_and_does_not_call_wcs_again(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->once() )
			->method( 'proxy_get' )
			->willReturn( new WP_REST_Response( array( 'campaigns' => array() ), 200 ) );

		$api = new CampaignApi( $wcs );

		$this->assertFalse( $api->has_active_campaigns( 'abc-123' ) );
		$this->assertFalse( $api->has_active_campaigns( 'abc-123' ) );
	}

	/**
	 * Test: the live check is only performed once; the second call reuses the
	 * cached `true` result instead of hitting WCS again.
	 */
	public function test_caches_true_result_and_does_not_call_wcs_again(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->once() )
			->method( 'proxy_get' )
			->willReturn(
				new WP_REST_Response(
					array( 'campaigns' => array( array( 'campaign' => array( 'id' => 'c-1' ) ) ) ),
					200
				)
			);

		$api = new CampaignApi( $wcs );

		$this->assertTrue( $api->has_active_campaigns( 'abc-123' ) );
		$this->assertTrue( $api->has_active_campaigns( 'abc-123' ) );
	}

	/**
	 * Test: `clear_cache()` removes the cached value, forcing the next call to
	 * hit the live endpoint again.
	 */
	public function test_clear_cache_forces_live_recheck(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->exactly( 2 ) )
			->method( 'proxy_get' )
			->willReturn( new WP_REST_Response( array( 'campaigns' => array() ), 200 ) );

		$api = new CampaignApi( $wcs );

		$this->assertFalse( $api->has_active_campaigns( 'abc-123' ) );

		$api->clear_cache();

		$this->assertFalse( $api->has_active_campaigns( 'abc-123' ) );
	}

	/**
	 * Test: `register_hooks()` wires `clear_cache()` to both connection-change hooks.
	 */
	public function test_register_hooks_busts_cache_on_onboarding_complete_and_disconnect(): void {
		Transients::set( TransientDefaults::CAMPAIGN_HAS_ACTIVE, '1' );

		$wcs = $this->createMock( WcsClient::class );
		$api = new CampaignApi( $wcs );
		$api->register_hooks();

		do_action( Helper::with_prefix( 'onboarding_complete' ) );
		$this->assertSame( '', Transients::get( TransientDefaults::CAMPAIGN_HAS_ACTIVE ) );

		Transients::set( TransientDefaults::CAMPAIGN_HAS_ACTIVE, '1' );

		do_action( Helper::with_prefix( 'snapchat_disconnected' ) );
		$this->assertSame( '', Transients::get( TransientDefaults::CAMPAIGN_HAS_ACTIVE ) );
	}
}
