<?php
/**
 * Unit tests for JetpackAccountController.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\API\Controllers
 */

namespace SnapchatForWooCommerce\Tests\Unit\API\Controllers;

use WP_REST_Request;
use WP_UnitTestCase;
use Automattic\Jetpack\Connection\Manager;
use SnapchatForWooCommerce\API\Site\Controllers\JetpackAccountController;
use SnapchatForWooCommerce\Connection\WcsClient;

/**
 * @covers \SnapchatForWooCommerce\API\Site\Controllers\JetpackAccountController
 */
class JetpackAccountControllerTest extends WP_UnitTestCase {

	/**
	 * Test: the authorization redirect carries the referrer params from the connect request.
	 */
	public function test_connect_appends_referrer_to_redirect(): void {
		$request = new WP_REST_Request( 'GET', '/wc/sfw/jetpack/connect' );
		$request->set_param( 'next_page_name', 'setup-snapchat' );
		$request->set_param( 'referrer_type', 'in_product_placement' );
		$request->set_param( 'referrer_id', 'channel-visibility-meta-box' );

		$this->run_connect(
			$request,
			admin_url( 'admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup&referrer_type=in_product_placement&referrer_id=channel-visibility-meta-box' )
		);
	}

	/**
	 * Test: without referrer params, the authorization redirect is unchanged.
	 */
	public function test_connect_keeps_redirect_without_referrer(): void {
		$request = new WP_REST_Request( 'GET', '/wc/sfw/jetpack/connect' );
		$request->set_param( 'next_page_name', 'setup-snapchat' );

		$this->run_connect( $request, admin_url( 'admin.php?page=wc-admin&path=/snapchat/setup' ) );
	}

	/**
	 * Runs the connect callback and asserts the redirect passed to Jetpack.
	 *
	 * @param WP_REST_Request $request           Connect request.
	 * @param string          $expected_redirect Redirect URL Jetpack should receive.
	 * @return void
	 */
	private function run_connect( WP_REST_Request $request, string $expected_redirect ): void {
		$manager = $this->createMock( Manager::class );
		$manager->method( 'is_connected' )->willReturn( false );
		$manager->method( 'register' )->willReturn( true );
		$manager->expects( $this->once() )
			->method( 'get_authorization_url' )
			->with( null, $expected_redirect )
			->willReturn( 'https://jetpack.example.com/authorize' );

		$controller = new JetpackAccountController( $this->createMock( WcsClient::class ), $manager );

		$method = new \ReflectionMethod( $controller, 'get_connect_callback' );
		$method->setAccessible( true );
		$callback = $method->invoke( $controller );

		$response = $callback( $request );

		$this->assertStringStartsWith( 'https://jetpack.example.com/authorize', $response->get_data()['url'] );
	}
}
