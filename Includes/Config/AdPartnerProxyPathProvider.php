<?php
namespace SnapchatForWoocommerce\Config;

use SnapchatForWoocommerce\Contracts\ProxyPathProviderInterface;

class AdPartnerProxyPathProvider implements ProxyPathProviderInterface {
	private const PATHS = [
		'pixel' => 'snapchat/snapchat-ads/adaccounts/{ad_account_id}/pixels',
	];

	public static function get_path( string $resource, array $params = [] ): string {
		if ( ! isset( self::PATHS[ $resource ] ) ) {
			throw new \InvalidArgumentException( "Unknown path key: {$resource}" );
		}

		return preg_replace_callback( '/\\{(.*?)\\}/', function ( $matches ) use ( $params ) {
			return $params[ $matches[1] ] ?? '';
		}, self::PATHS[ $resource ] );
	}
}
