<?php

namespace SnapchatForWooCommerce\Admin;

class Setup {
	protected $services = [];

	public function __construct( ...$services ) {
		$this->services = $services;
	}

	public function init() {
		foreach ( $this->services as $service ) {
			if ( method_exists( $service, 'register_hooks' ) ) {
				$service->register_hooks();
			}
		}
	}
}
