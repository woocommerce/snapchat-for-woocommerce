<?php
/**
 * Provides a safe, simulated post-onboarding experience.
 *
 * @package SnapchatForWooCommerce
 */

namespace SnapchatForWooCommerce;

use SnapchatForWooCommerce\Admin\Export\Service\ProductExportService;
use SnapchatForWooCommerce\Admin\Export\Service\ProductIdCacheBuilder;
use SnapchatForWooCommerce\Utils\Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Projects dummy connected-account data while preventing remote side effects.
 */
final class SandboxMode {
	/**
	 * The manually managed WordPress option that enables sandbox mode.
	 */
	public const OPTION_NAME = 'snapchat_sandbox_mode';

	/**
	 * Isolated storage for settings changed while sandbox mode is active.
	 */
	public const SETTINGS_OPTION_NAME = 'snapchat_sandbox_settings';

	/**
	 * Registers the sandbox hooks.
	 */
	public function register_hooks(): void {
		add_filter( 'pre_option_snapchat_onboarding_status', array( $this, 'filter_onboarding_status' ) );
		add_filter( 'pre_option_snapchat_onboarding_step', array( $this, 'filter_onboarding_step' ) );
		add_filter( 'pre_option_snapchat_ads_pixel_enabled', array( $this, 'disable_tracking' ) );
		add_filter( 'pre_option_woocommerce_allow_tracking', array( $this, 'disable_tracking' ) );
		add_filter( 'pre_option_snapchat_conversion_enabled', array( $this, 'filter_conversion_setting' ) );
		add_filter( 'pre_option_snapchat_collect_pii', array( $this, 'filter_collect_pii_setting' ) );
		add_filter( 'pre_update_option_snapchat_conversion_enabled', array( $this, 'save_conversion_setting' ), 10, 2 );
		add_filter( 'pre_update_option_snapchat_collect_pii', array( $this, 'save_collect_pii_setting' ), 10, 2 );
		add_filter( 'rest_dispatch_request', array( $this, 'intercept_rest_request' ), 10, 2 );
		add_action( 'init', array( $this, 'disable_remote_product_hooks' ), PHP_INT_MAX );
	}

	/**
	 * Removes the sandbox hooks. Primarily useful for isolated tests.
	 */
	public function unregister_hooks(): void {
		remove_filter( 'pre_option_snapchat_onboarding_status', array( $this, 'filter_onboarding_status' ) );
		remove_filter( 'pre_option_snapchat_onboarding_step', array( $this, 'filter_onboarding_step' ) );
		remove_filter( 'pre_option_snapchat_ads_pixel_enabled', array( $this, 'disable_tracking' ) );
		remove_filter( 'pre_option_woocommerce_allow_tracking', array( $this, 'disable_tracking' ) );
		remove_filter( 'pre_option_snapchat_conversion_enabled', array( $this, 'filter_conversion_setting' ) );
		remove_filter( 'pre_option_snapchat_collect_pii', array( $this, 'filter_collect_pii_setting' ) );
		remove_filter( 'pre_update_option_snapchat_conversion_enabled', array( $this, 'save_conversion_setting' ), 10 );
		remove_filter( 'pre_update_option_snapchat_collect_pii', array( $this, 'save_collect_pii_setting' ), 10 );
		remove_filter( 'rest_dispatch_request', array( $this, 'intercept_rest_request' ), 10 );
		remove_action( 'init', array( $this, 'disable_remote_product_hooks' ), PHP_INT_MAX );
	}

	/**
	 * Returns whether sandbox mode is enabled by its database option.
	 */
	public static function is_enabled(): bool {
		$value = get_option( self::OPTION_NAME, 'no' );

		if ( true === $value || 1 === $value ) {
			return true;
		}

		return in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Makes the channel appear onboarded without changing the live option.
	 *
	 * @param mixed $value Existing short-circuit value.
	 * @return mixed
	 */
	public function filter_onboarding_status( $value ) {
		return self::is_enabled() ? 'connected' : $value;
	}

	/**
	 * Keeps the simulated setup on the accounts step.
	 *
	 * @param mixed $value Existing short-circuit value.
	 * @return mixed
	 */
	public function filter_onboarding_step( $value ) {
		return self::is_enabled() ? 'accounts' : $value;
	}

	/**
	 * Disables storefront tracking while sandbox mode is active.
	 *
	 * @param mixed $value Existing short-circuit value.
	 * @return mixed
	 */
	public function disable_tracking( $value ) {
		return self::is_enabled() ? 'no' : $value;
	}

	/**
	 * Returns the isolated conversions setting in admin/REST contexts.
	 *
	 * Tracking remains disabled for storefront and asynchronous requests.
	 *
	 * @param mixed $value Existing short-circuit value.
	 * @return mixed
	 */
	public function filter_conversion_setting( $value ) {
		if ( ! self::is_enabled() ) {
			return $value;
		}

		if ( $this->is_settings_context() ) {
			return $this->get_sandbox_setting( 'conversion_enabled', 'yes' );
		}

		return 'no';
	}

	/**
	 * Returns the isolated PII setting while sandbox mode is active.
	 *
	 * @param mixed $value Existing short-circuit value.
	 * @return mixed
	 */
	public function filter_collect_pii_setting( $value ) {
		return self::is_enabled() ? $this->get_sandbox_setting( 'collect_pii', 'yes' ) : $value;
	}

	/**
	 * Saves the sandbox conversions setting without updating its live counterpart.
	 *
	 * @param mixed $new_value Proposed setting value.
	 * @param mixed $old_value Current live setting value.
	 * @return mixed
	 */
	public function save_conversion_setting( $new_value, $old_value ) {
		if ( ! self::is_enabled() ) {
			return $new_value;
		}

		$this->set_sandbox_setting( 'conversion_enabled', $new_value );
		return $old_value;
	}

	/**
	 * Saves the sandbox PII setting without updating its live counterpart.
	 *
	 * @param mixed $new_value Proposed setting value.
	 * @param mixed $old_value Current live setting value.
	 * @return mixed
	 */
	public function save_collect_pii_setting( $new_value, $old_value ) {
		if ( ! self::is_enabled() ) {
			return $new_value;
		}

		$this->set_sandbox_setting( 'collect_pii', $new_value );
		return $old_value;
	}

	/**
	 * Returns simulated account data after normal REST permission checks pass.
	 *
	 * @param WP_REST_Response|WP_Error|null $response Existing dispatch result.
	 * @param WP_REST_Request                $request  REST request.
	 * @return WP_REST_Response|WP_Error|null
	 */
	public function intercept_rest_request( $response, $request ) {
		if ( ! self::is_enabled() || null !== $response ) {
			return $response;
		}

		$route  = $request->get_route();
		$method = $request->get_method();

		if ( '/wc/sfw/snapchat/setup' === $route && 'GET' === $method ) {
			return new WP_REST_Response(
				array(
					'status' => 'connected',
					'step'   => 'accounts',
				)
			);
		}

		if ( '/wc/sfw/jetpack/connected' === $route && 'GET' === $method ) {
			return new WP_REST_Response(
				array(
					'active'      => 'yes',
					'owner'       => 'yes',
					'displayName' => __( 'Sandbox Merchant', 'snapchat-for-woocommerce' ),
					'email'       => 'sandbox@example.com',
				)
			);
		}

		if ( '/wc/sfw/snapchat/account' === $route && 'GET' === $method ) {
			return new WP_REST_Response(
				array(
					'org_id'      => 'sandbox-organization',
					'org_name'    => __( 'Sandbox Organization', 'snapchat-for-woocommerce' ),
					'ad_acc_id'   => 'sandbox-ad-account',
					'ad_acc_name' => __( 'Sandbox Ad Account', 'snapchat-for-woocommerce' ),
					'pixel_id'    => 'sandbox-pixel',
				)
			);
		}

		if ( '/wc/sfw/snapchat/connection' === $route && 'GET' === $method ) {
			return new WP_REST_Response( array( 'status' => 'connected' ) );
		}

		$blocked_routes = array(
			'/wc/sfw/jetpack/connect',
			'/wc/sfw/snapchat/connect',
			'/wc/sfw/snapchat/config',
			'/wc/sfw/snapchat/connection',
		);

		if ( in_array( $route, $blocked_routes, true ) ) {
			return new WP_Error(
				'snapchat_sandbox_action_disabled',
				__( 'Account connection actions are disabled in sandbox mode.', 'snapchat-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return $response;
	}

	/**
	 * Removes callbacks that would register or synchronize remote product feeds.
	 */
	public function disable_remote_product_hooks(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$service = ServiceContainer::get( ServiceKey::PRODUCT_EXPORT_SERVICE );

		remove_action( Helper::with_prefix( 'onboarding_complete' ), array( $service, 'start_export_after_onboarding' ) );
		remove_action( Helper::with_prefix( 'recurring_catalog_export' ), array( $service, 'start_export' ) );
		remove_action( Helper::with_prefix( 'export_products_cache_completed' ), array( $service, 'start_writing' ) );
		remove_action( Helper::with_prefix( ProductExportService::ACTION_HOOK ), array( $service, 'handle_batch' ), 10 );
		remove_action( Helper::with_prefix( 'batch_export_job_complete' ), array( $service, 'create_feed' ) );
		remove_action( 'wp_ajax_' . Helper::with_prefix( 'generate_feed' ), array( $service, 'trigger_export_callback' ) );
		remove_filter( 'wp_ajax_' . Helper::with_prefix( 'export_status' ), array( $service, 'check_export_status' ), 10 );
		remove_action(
			Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ),
			array( $service->job->cache_builder, 'handle_batch' ),
			10
		);
	}

	/**
	 * Whether settings should be visible instead of forced off for runtime safety.
	 */
	private function is_settings_context(): bool {
		$is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;
		$is_admin_page   = is_admin() && ! wp_doing_ajax();

		return $is_rest_request || $is_admin_page;
	}

	/**
	 * Reads one setting from the isolated sandbox option.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Default value.
	 * @return mixed
	 */
	private function get_sandbox_setting( string $key, $fallback ) {
		$settings = get_option( self::SETTINGS_OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			return $fallback;
		}

		return $settings[ $key ] ?? $fallback;
	}

	/**
	 * Writes one setting to the isolated sandbox option.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 */
	private function set_sandbox_setting( string $key, $value ): void {
		$settings = get_option( self::SETTINGS_OPTION_NAME, array() );
		$settings = is_array( $settings ) ? $settings : array();

		$settings[ $key ] = $value;
		update_option( self::SETTINGS_OPTION_NAME, $settings );
	}
}
