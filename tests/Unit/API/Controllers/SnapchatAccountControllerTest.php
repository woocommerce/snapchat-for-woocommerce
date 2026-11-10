<?php
/**
 * Unit tests for SnapchatAccountController.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\API\Controllers
 */

namespace SnapchatForWooCommerce\Tests\Unit\API\Controllers;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Campaign\CampaignStatusService;
use SnapchatForWooCommerce\API\Site\Controllers\SnapchatAccountController;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\API\Site\Controllers\SnapchatAccountController
 */
class SnapchatAccountControllerTest extends WP_UnitTestCase {

	/**
	 * Clear the ad account option the tests mutate.
	 */
	public function tear_down(): void {
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		parent::tear_down();
	}

	/**
	 * Test: a cached campaign result is reported without a live check.
	 */
	public function test_account_details_report_cached_campaign_status(): void {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'test-ad-account' );

		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->expects( $this->once() )->method( 'get_cached_status' )->willReturn( true );
		$campaign_status->expects( $this->never() )->method( 'refresh_cache' );

		$data = ( new SnapchatAccountController( $campaign_status ) )->get_account_details()->get_data();

		$this->assertTrue( $data['has_active_campaign'] );
	}

	/**
	 * Test: on a cache miss, a live check runs and its result is reported.
	 */
	public function test_account_details_refresh_campaign_status_on_cache_miss(): void {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'test-ad-account' );

		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->expects( $this->exactly( 2 ) )
			->method( 'get_cached_status' )
			->willReturnOnConsecutiveCalls( null, false );
		$campaign_status->expects( $this->once() )->method( 'refresh_cache' );

		$data = ( new SnapchatAccountController( $campaign_status ) )->get_account_details()->get_data();

		$this->assertFalse( $data['has_active_campaign'] );
	}

	/**
	 * Test: when the live check fails, `has_active_campaign` is null rather than a guess.
	 */
	public function test_account_details_report_null_when_campaign_check_fails(): void {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'test-ad-account' );

		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->method( 'get_cached_status' )->willReturn( null );
		$campaign_status->expects( $this->once() )->method( 'refresh_cache' );

		$data = ( new SnapchatAccountController( $campaign_status ) )->get_account_details()->get_data();

		$this->assertNull( $data['has_active_campaign'] );
	}

	/**
	 * Test: without an ad account, `has_active_campaign` is false and no lookup runs.
	 */
	public function test_account_details_report_no_campaign_without_ad_account(): void {
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );

		$campaign_status = $this->createMock( CampaignStatusService::class );
		$campaign_status->expects( $this->never() )->method( 'get_cached_status' );
		$campaign_status->expects( $this->never() )->method( 'refresh_cache' );

		$data = ( new SnapchatAccountController( $campaign_status ) )->get_account_details()->get_data();

		$this->assertFalse( $data['has_active_campaign'] );
	}
}
