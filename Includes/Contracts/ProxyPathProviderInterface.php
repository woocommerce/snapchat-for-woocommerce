<?php

namespace SnapchatForWoocommerce\Contracts;

interface ProxyPathProviderInterface {
	public static function get_path( string $resource, array $params = [] ): string;
}
