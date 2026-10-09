<?php
/**
 * API module for checking whether the connected ad account has any campaigns.
 *
 * The check runs in a background Action Scheduler job and its result is
 * cached in a transient, so the order edit screen only ever reads the cache
 * and is never delayed by a slow or failing WooCommerce Connect Server (WCS)
 * request. The cache is busted on Snapchat connection changes (see
 * {@see self::register_hooks()}).
 *
 * @since n.e.x.t
 * @package SnapchatForWooCommerce\API\AdPartner
 */

namespace SnapchatForWooCommerce\API\AdPartner;

use SnapchatForWooCommerce\API\AdPartner\BaseAdPartnerApi;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * API module for checking whether the connected ad account has any campaigns.
 *
 * @since n.e.x.t
 */
class CampaignApi extends BaseAdPartnerApi {

	/**
	 * Action Scheduler hook (without prefix) for the background campaign check.
	 *
	 * @since n.e.x.t
	 */
	public const ACTION_HOOK = 'refresh_campaign_cache';

	/**
	 * Registers the background job handler and the hooks that bust the campaign cache.
	 *
	 * The cache is cleared whenever the Snapchat connection changes: on
	 * `onboarding_complete` (fired once at the end of the OAuth config-save
	 * flow, which covers connect, reconnect, and ad-account change since they
	 * all share that save path) and on `snapchat_disconnected`.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( Helper::with_prefix( self::ACTION_HOOK ), array( $this, 'refresh_cache' ) );
		add_action( Helper::with_prefix( 'onboarding_complete' ), array( $this, 'clear_cache' ) );
		add_action( Helper::with_prefix( 'snapchat_disconnected' ), array( $this, 'clear_cache' ) );
	}

	/**
	 * Clears the cached `has_campaigns()` result.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		Transients::delete( TransientDefaults::HAS_CAMPAIGNS );
	}

	/**
	 * Whether the connected ad account has any campaign, active or paused.
	 *
	 * Only reads the cache. On a cache miss, schedules a background check and
	 * returns true, so the create-campaign banner stays hidden until the
	 * result is known.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool True if the ad account has at least one campaign, or if the
	 *              result is not known yet.
	 */
	public function has_campaigns(): bool {
		$cached = Transients::get( TransientDefaults::HAS_CAMPAIGNS );

		if ( '' !== $cached ) {
			return '1' === $cached;
		}

		$this->schedule_refresh();

		return true;
	}

	/**
	 * Schedules the background campaign check, unless one is already pending.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function schedule_refresh(): void {
		$hook = Helper::with_prefix( self::ACTION_HOOK );

		if ( as_has_scheduled_action( $hook ) ) {
			return;
		}

		as_enqueue_async_action( $hook, array(), Config::PLUGIN_SLUG );
	}

	/**
	 * Checks whether the connected ad account has any campaigns and caches the result.
	 *
	 * Runs via Action Scheduler. Cached as '1'/'0' because a cached false reads
	 * as a cache miss. Failures are not cached, so the next cache miss schedules
	 * another check.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function refresh_cache(): void {
		$ad_account_id = (string) Options::get( OptionDefaults::AD_ACCOUNT_ID );

		if ( '' === $ad_account_id ) {
			return;
		}

		$response = $this->wcs->proxy_get(
			'/v1/adaccounts/' . rawurlencode( $ad_account_id ) . '/campaigns'
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		$data      = $response->get_data();
		$campaigns = ( isset( $data['campaigns'] ) && is_array( $data['campaigns'] ) ) ? $data['campaigns'] : array();

		Transients::set( TransientDefaults::HAS_CAMPAIGNS, empty( $campaigns ) ? '0' : '1' );
	}
}
