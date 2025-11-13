<?php
/**
 * Central service container for managing Ad Partner plugin dependencies.
 *
 * This static container resolves and stores instances of services like connection handlers,
 * authenticators, API clients, and tracking systems, ensuring singleton-like access.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Tracking\PixelTrackingService;
use SnapchatForWooCommerce\Tracking\RemotePixelTracker;
use SnapchatForWooCommerce\API;
use SnapchatForWooCommerce\Connection;
use SnapchatForWooCommerce\Tracking;
use SnapchatForWooCommerce\Admin;
use SnapchatForWooCommerce\Admin\Export;
use SnapchatForWooCommerce\Admin\ProductMeta;
use SnapchatForWooCommerce\Tracking\ConversionEventLogger;
use SnapchatForWooCommerce\API\AdPartner\AdPartnerApi;
use SnapchatForWooCommerce\Utils\ProductData\ProductCategoryProvider;
use function wc_get_logger;


/**
 * Static service container for resolving shared instances across the Ad Partner plugin.
 *
 * This container lazily initializes services the first time they are requested and stores
 * them for subsequent use. Services are identified by string keys (e.g., 'connection', 'wcs_client').
 *
 * It simplifies dependency injection and ensures consistency across the plugin’s components.
 */
final class ServiceContainer {
	/**
	 * Stores resolved service instances.
	 *
	 * @var array<string,object>
	 */
	private static $instances = array();

	/**
	 * Retrieves a shared instance of a requested service.
	 *
	 * If the service has not yet been created, it is resolved and cached internally.
	 *
	 * @since 0.1.0
	 *
	 * @param string $service Name of the service to retrieve (e.g., 'connection').
	 *
	 * @return object The resolved service instance.
	 */
	public static function get( string $service ) {
		if ( ! isset( self::$instances[ $service ] ) ) {
			self::$instances[ $service ] = self::resolve( $service );
		}

		return self::$instances[ $service ];
	}

	/**
	 * Resolves and instantiates a service based on its string identifier.
	 *
	 * This acts as the internal factory for all supported services.
	 *
	 * @since 0.1.0
	 *
	 * @param string $service The service name.
	 *
	 * @return object The newly instantiated service.
	 *
	 * @throws \InvalidArgumentException If the requested service is unknown.
	 */
	private static function resolve( string $service ) {
		switch ( $service ) {
			case ServiceKey::SETTINGS_REST_CONTROLLER_SETUP:
				return new API\SetupService();
			case ServiceKey::JETPACK_AUTHENTICATOR:
				return new Connection\JetpackAuthenticator();
			case ServiceKey::WCS_CLIENT:
				return new WcsClient(
					self::get( ServiceKey::JETPACK_AUTHENTICATOR ),
					new Connection\JetpackClient()
				);
			case ServiceKey::PIXEL_TRACKING:
				return new PixelTrackingService(
					new RemotePixelTracker(
						self::get( ServiceKey::WCS_CLIENT )
					)
				);
			case ServiceKey::CONVERSION_TRACKING:
				return new Tracking\ConversionTrackingService(
					new Tracking\RemoteConversionTracker(
						self::get( ServiceKey::WCS_CLIENT ),
						new ConversionEventLogger(
							wc_get_logger()
						)
					)
				);
			case ServiceKey::PRODUCT_EXPORT_SERVICE:
				return new Export\Service\ProductExportService(
					new Export\BatchExportJob(
						new Export\Service\ProductIdCacheBuilder(),
						new Export\EntityProvider\ProductEntityProvider(),
						new Export\RowBuilder\ProductRowBuilder(
							array(
								new ProductCategoryProvider(),
							)
						),
						new Export\Writer\CsvExportWriter(),
						AdPartnerApi::get_instance(
							self::get( ServiceKey::WCS_CLIENT )
						),
					)
				);
			case ServiceKey::ADMIN_SETUP:
				return new Admin\Setup(
					new Admin\Assets(),
					new Admin\Menu(),
					new Admin\Onboarding(),
					new ProductMeta\ProductMetaFields(),
					new Admin\Notices(),
				);

			default:
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \InvalidArgumentException( "Unknown service: $service" );
		}
	}
}
