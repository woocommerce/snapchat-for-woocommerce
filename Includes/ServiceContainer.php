<?php
namespace SnapchatForWooCommerce;

use SnapchatForWooCommerce\Connection\ConnectionService;
use SnapchatForWooCommerce\Connection\JetpackAuthenticator;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Tracking\PixelTrackingService;
use SnapchatForWooCommerce\Tracking\RemotePixelTracker;

final class ServiceContainer {
	private static $instances = [];

	public static function get( string $service ) {
		if ( ! isset( self::$instances[ $service ] ) ) {
			self::$instances[ $service ] = self::resolve( $service );
		}
		return self::$instances[ $service ];
	}

	private static function resolve( string $service ) {
		switch ( $service ) {
			case 'connection':
				return new ConnectionService( self::get( 'wcs_client' ), self::get( 'jetpack_authenticator' ) );
			case 'jetpack_authenticator':
				return new JetpackAuthenticator();
			case 'wcs_client':
				return new WcsClient();
			case 'pixel_tracking':
				return new PixelTrackingService(
					new RemotePixelTracker(
						self::get( 'wcs_client' ),
						self::get( 'jetpack_authenticator' )
					)
				);

			default:
				throw new \InvalidArgumentException( "Unknown service: $service" );
		}
	}
}
