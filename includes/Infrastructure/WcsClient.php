<?php

namespace SnapchatForWoocommerce\Infrastructure;

class WcsClient {
	private string $base_url;

	public function __construct( string $base_url ) {
		$this->base_url = rtrim( $base_url, '/' );
	}

	public function get_url_for( string $path ): string {
		return $this->base_url . '/' . ltrim( $path, '/' );
	}

	public function get_base_url(): string {
		return $this->base_url;
	}
}
