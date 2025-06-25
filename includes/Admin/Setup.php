<?php
/**
 * Admin setup class for Snapchat for WooCommerce.
 *
 * Coordinates registration of admin-facing services, including:
 * - Script and style enqueues
 * - Product meta fields
 * - WooCommerce Admin page integration
 *
 * This class uses constructor-based dependency injection to allow
 * flexible registration of admin services that implement a `register_hooks()` method.
 *
 * @package SnapchatForWooCommerce\Admin
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin;

/**
 * Coordinates admin-side service initialization for the plugin.
 *
 * This class accepts any number of service objects that support the
 * `register_hooks()` method, and invokes them during admin bootstrap.
 * It allows granular registration of features such as product metadata,
 * script assets, and admin UI components without hardcoding dependencies.
 *
 * @since 0.1.0
 */
class Setup {

	/**
	 * List of admin service instances.
	 *
	 * Each service must implement a `register_hooks()` method.
	 *
	 * @var array<int,object>
	 */
	protected array $services = array();

	/**
	 * Constructor.
	 *
	 * Accepts an arbitrary number of services that will be stored for later initialization.
	 *
	 * @since 0.1.0
	 *
	 * @param object ...$services Services that expose a `register_hooks()` method.
	 */
	public function __construct( ...$services ) {
		$this->services = $services;
	}

	/**
	 * Calls `register_hooks()` on each injected service.
	 *
	 * This method is intended to be invoked during plugin bootstrap.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init(): void {
		foreach ( $this->services as $service ) {
			if ( method_exists( $service, 'register_hooks' ) ) {
				$service->register_hooks();
			}
		}
	}
}
