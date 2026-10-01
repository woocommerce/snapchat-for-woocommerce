<?php
/**
 * Unit tests for SnapchatAccountController.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\API\Controllers
 */

namespace SnapchatForWooCommerce\Tests\Unit\API\Controllers;

use WP_UnitTestCase;
use SnapchatForWooCommerce\API\AdPartner\CampaignApi;
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
	 * Test: `has_active_campaign` reflects the campaign lookup for the stored ad account.
	 */
	public function test_account_details_include_active_campaign_flag(): void {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'test-ad-account' );

		$campaign_api = $this->createMock( CampaignApi::class );
		$campaign_api->expects( $this->once() )
			->method( 'has_active_campaigns' )
			->with( 'test-ad-account' )
			->willReturn( true );

		$data = ( new SnapchatAccountController( $campaign_api ) )->get_account_details()->get_data();

		$this->assertTrue( $data['has_active_campaign'] );
	}

	/**
	 * Test: without an ad account, `has_active_campaign` is false and no lookup runs.
	 */
	public function test_account_details_report_no_campaign_without_ad_account(): void {
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );

		$campaign_api = $this->createMock( CampaignApi::class );
		$campaign_api->expects( $this->never() )->method( 'has_active_campaigns' );

		$data = ( new SnapchatAccountController( $campaign_api ) )->get_account_details()->get_data();

		$this->assertFalse( $data['has_active_campaign'] );
	}
}
