<?php
/**
 * Unit tests for CampaignApi.
 *
 * Covers `get_campaigns()` proxying to the Ad Partner campaigns endpoint.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\API\AdPartner
 */

namespace SnapchatForWooCommerce\Tests\Unit\API\AdPartner;

use WP_Error;
use WP_REST_Response;
use WP_UnitTestCase;
use SnapchatForWooCommerce\API\AdPartner\CampaignApi;
use SnapchatForWooCommerce\Connection\WcsClient;

/**
 * @covers \SnapchatForWooCommerce\API\AdPartner\CampaignApi
 */
class CampaignApiTest extends WP_UnitTestCase {

	/**
	 * Test: `get_campaigns()` proxies GET to `/ads/v1/adaccounts/{id}/campaigns`
	 * with the given ad account ID URL-encoded, and returns the WCS response.
	 */
	public function test_get_campaigns_proxies_to_correct_endpoint(): void {
		$response = new WP_REST_Response( array( 'campaigns' => array() ), 200 );

		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->once() )
			->method( 'proxy_get' )
			->with( '/ads/v1/adaccounts/abc%20123/campaigns' )
			->willReturn( $response );

		$this->assertSame( $response, ( new CampaignApi( $wcs ) )->get_campaigns( 'abc 123' ) );
	}

	/**
	 * Test: `get_campaigns()` passes a WCS error straight through.
	 */
	public function test_get_campaigns_returns_wp_error(): void {
		$error = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );

		$wcs = $this->createMock( WcsClient::class );
		$wcs->method( 'proxy_get' )->willReturn( $error );

		$this->assertSame( $error, ( new CampaignApi( $wcs ) )->get_campaigns( 'abc-123' ) );
	}
}
