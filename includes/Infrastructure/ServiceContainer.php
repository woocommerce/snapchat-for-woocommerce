<?php
namespace SnapchatForWoocommerce\Infrastructure;

use SnapchatForWoocommerce\Config\AdPartnerConfig;
use SnapchatForWoocommerce\Infrastructure\JetpackAuthenticator;
use SnapchatForWoocommerce\API\ConnectionService;
use SnapchatForWoocommerce\API\PixelTrackingService;
use SnapchatForWoocommerce\Config\OptionDefaults;
use Automattic\Jetpack\Connection\Manager;

class ServiceContainer {
	private array $instances = [];
	private string $options_prefix;

	public function __construct( string $options_prefix = 'snapchat_ads' ) {
		$this->options_prefix = $options_prefix;
		OptionDefaults::set_prefix( $this->options_prefix );
	}

	public function get( string $key ) {
		if ( ! isset( $this->instances[ $key ] ) ) {
			$this->instances[ $key ] = $this->make( $key );
		}
		return $this->instances[ $key ];
	}

	private function make( string $key ) {
		switch ( $key ) {
			case 'config':
				return new AdPartnerConfig();

			case 'wcs':
				return new WcsClient( 'https://wcs-mock.mylocal/wp-json/mock-wcs/v1' );

			case 'auth':
				return new JetpackAuthenticator( new Manager( 'snapchat-for-woocommerce' ) );

			case 'connection':
				return new ConnectionService(
					$this->get( 'config' ),
					$this->get( 'wcs' ),
					$this->get( 'auth' )
				);

			case 'pixel':
				$account_id = get_option( OptionDefaults::AD_ACCOUNT_ID, '' );
				return new PixelTrackingService(
					$this->get( 'wcs' ),
					$this->get( 'auth' ),
					$account_id
				);
		}

		throw new \InvalidArgumentException( "Unknown service: {$key}" );
	}
}
