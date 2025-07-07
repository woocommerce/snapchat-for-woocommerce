<?php
/**
 * Integration tests for the ServiceContainer class.
 *
 * @package SnapchatForWooCommerce\Tests\Integration
 */

namespace SnapchatForWooCommerce\Tests\Integration;

use WP_UnitTestCase;
use SnapchatForWooCommerce\ServiceContainer;
use SnapchatForWooCommerce\ServiceKey;
use SnapchatForWooCommerce\Connection\ConnectionService;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Tracking\PixelTrackingService;

/**
 * Integration tests for \SnapchatForWooCommerce\ServiceContainer.
 *
 * These are behavior-only tests. They confirm:
 * - Services are resolved correctly by key
 * - Services are singletons (same instance returned every time)
 * - An exception is thrown for unknown service keys
 *
 * These tests do not validate runtime behavior of the services themselves.
 */
class ServiceContainerTest extends WP_UnitTestCase {

	protected function tearDown(): void {
		parent::tearDown();

		$ref  = new \ReflectionClass( ServiceContainer::class );
		$prop = $ref->getProperty( 'instances' );
		$prop->setAccessible( true );
		$prop->setValue( array() );
	}

	/**
	 * Manually injects a service instance into the container.
	 *
	 * @param string $key
	 * @param object $instance
	 */
	private function set_service( string $key, object $instance ): void {
		$ref  = new \ReflectionClass( ServiceContainer::class );
		$prop = $ref->getProperty( 'instances' );
		$prop->setAccessible( true );

		$instances         = $prop->getValue();
		$instances[ $key ] = $instance;
		$prop->setValue( $instances );
	}

	/**
	 * Asserts that the WCS client service is resolved correctly.
	 */
	public function test_it_returns_wcs_client_instance() {
		$instance = ServiceContainer::get( ServiceKey::WCS_CLIENT );
		$this->assertInstanceOf( WcsClient::class, $instance );
	}

	/**
	 * Asserts that the Jetpack authenticator is resolved correctly.
	 */
	public function test_it_returns_jetpack_authenticator_instance() {
		$mock = $this->createMock( JetpackAuthenticator::class );
		$this->set_service( ServiceKey::JETPACK_AUTHENTICATOR, $mock );

		$instance = ServiceContainer::get( ServiceKey::JETPACK_AUTHENTICATOR );
		$this->assertInstanceOf( JetpackAuthenticator::class, $instance );
	}

	/**
	 * Asserts that the PixelTrackingService is resolved and properly constructed.
	 */
	public function test_it_returns_pixel_tracking_service_instance() {
		$mock = $this->createMock( JetpackAuthenticator::class );
		$this->set_service( ServiceKey::JETPACK_AUTHENTICATOR, $mock );

		$instance = ServiceContainer::get( ServiceKey::PIXEL_TRACKING );
		$this->assertInstanceOf( PixelTrackingService::class, $instance );
	}

	/**
	 * Asserts that the same instance is returned for multiple calls to the same service.
	 */
	public function test_it_returns_same_instance_on_multiple_calls() {
		$first  = ServiceContainer::get( ServiceKey::WCS_CLIENT );
		$second = ServiceContainer::get( ServiceKey::WCS_CLIENT );

		$this->assertSame( $first, $second );
	}

	/**
	 * Asserts that an exception is thrown when requesting an undefined service key.
	 */
	public function test_unknown_service_throws_exception() {
		$this->expectException( \InvalidArgumentException::class );
		ServiceContainer::get( 'nonexistent_service' );
	}
}
