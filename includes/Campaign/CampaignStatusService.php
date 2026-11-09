<?php
/**
 * Service for checking whether the connected ad account has any campaigns.
 *
 * The result is cached in a transient. The check against the WooCommerce
 * Connect Server (WCS) runs in a background Action Scheduler job on a cache
 * miss, straight away when the Snapchat account is connected, and live from
 * the `/account` REST endpoint on a cache miss. The order edit screen's PHP
 * render only reads the cache, so a slow or failing WCS request never delays
 * the page itself. The cache is busted on Snapchat connection changes (see
 * {@see self::register_hooks()}).
 *
 * @since n.e.x.t
 * @package SnapchatForWooCommerce\Campaign
 */

namespace SnapchatForWooCommerce\Campaign;

use SnapchatForWooCommerce\API\AdPartner\AdPartnerApi;
use SnapchatForWooCommerce\Config;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * Caches whether the connected ad account has any campaigns.
 *
 * @since n.e.x.t
 */
class CampaignStatusService {

	/**
	 * Action Scheduler hook (without prefix) for the background campaign check.
	 *
	 * @since n.e.x.t
	 */
	public const ACTION_HOOK = 'refresh_campaign_cache';

	/**
	 * Ad Partner API used to request the ad account's campaigns.
	 *
	 * @since n.e.x.t
	 *
	 * @var AdPartnerApi
	 */
	protected AdPartnerApi $ad_partner_api;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param AdPartnerApi $ad_partner_api Ad Partner API.
	 */
	public function __construct( AdPartnerApi $ad_partner_api ) {
		$this->ad_partner_api = $ad_partner_api;
	}

	/**
	 * Registers the background job handler and the connection-change hooks.
	 *
	 * The cache is cleared whenever the Snapchat connection changes. Despite
	 * its name, `onboarding_complete` fires when the Snapchat account is
	 * connected (the OAuth config save, for connect, reconnect and ad-account
	 * change), before the merchant finishes the onboarding steps. There the
	 * check then runs straight away. On `snapchat_disconnected`, any queued
	 * background check is also cancelled. The cached result is tagged with
	 * its ad account, so a change that skips these hooks still never serves
	 * a stale result.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( Helper::with_prefix( self::ACTION_HOOK ), array( $this, 'refresh_cache' ) );
		add_action( Helper::with_prefix( 'onboarding_complete' ), array( $this, 'refresh_on_connect' ) );
		add_action( Helper::with_prefix( 'snapchat_disconnected' ), array( $this, 'clear_cache' ) );
		add_action( Helper::with_prefix( 'snapchat_disconnected' ), array( $this, 'unschedule_refresh' ) );
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
	 * Clears the cache and runs the campaign check straight away.
	 *
	 * Runs on `onboarding_complete`, which fires when the Snapchat account is
	 * connected, so the result for the newly connected ad account is usually
	 * known by the time the merchant clicks Continue on the Set up accounts
	 * step, instead of waiting for the next cache miss. A failed check isn't
	 * cached, so the result stays unknown until the next check.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function refresh_on_connect(): void {
		$this->clear_cache();
		$this->refresh_cache();
	}

	/**
	 * Cancels any queued background campaign check.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function unschedule_refresh(): void {
		as_unschedule_all_actions( Helper::with_prefix( self::ACTION_HOOK ) );
	}

	/**
	 * Whether the connected ad account has any campaign, active or paused.
	 *
	 * Only reads the cache. The cached result is tagged with the ad account it
	 * was checked for, and is only used while that account is still the
	 * connected one. On a cache miss or an account mismatch, schedules a
	 * background check and returns true, so the create-campaign banner stays
	 * hidden until the result is known.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool True if the ad account has at least one campaign, or if the
	 *              result is not known yet.
	 */
	public function has_campaigns(): bool {
		$status = $this->get_cached_status();

		if ( null !== $status ) {
			return $status;
		}

		$this->schedule_refresh();

		return true;
	}

	/**
	 * Returns the cached campaign result for the connected ad account, if known.
	 *
	 * Unlike {@see self::has_campaigns()}, this neither schedules a check nor
	 * falls back to true, so callers that report the result (rather than use it
	 * to hide a banner) can tell "no campaigns" apart from "not known yet".
	 *
	 * @since n.e.x.t
	 *
	 * @return bool|null Whether the ad account has any campaign, or null when
	 *                   no result is cached for the connected ad account.
	 */
	public function get_cached_status(): ?bool {
		$cached = Transients::get( TransientDefaults::HAS_CAMPAIGNS );

		if (
			is_array( $cached )
			&& isset( $cached['ad_account_id'], $cached['has_campaigns'] )
			&& (string) Options::get( OptionDefaults::AD_ACCOUNT_ID ) === $cached['ad_account_id']
		) {
			return (bool) $cached['has_campaigns'];
		}

		return null;
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
	 * Runs synchronously. It is called from the background Action Scheduler
	 * job, from {@see self::refresh_on_connect()}, and by the `/account` REST
	 * endpoint on a cache miss. The result is cached together with the ad
	 * account ID it was checked for, so a result for a previous account is
	 * never used after the account changes. Failures and unexpected responses
	 * are not cached, so the next cache miss runs another check.
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

		$response = $this->ad_partner_api->campaign->get_campaigns( $ad_account_id );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$data = $response->get_data();

		if ( ! isset( $data['campaigns'] ) || ! is_array( $data['campaigns'] ) ) {
			return;
		}

		Transients::set(
			TransientDefaults::HAS_CAMPAIGNS,
			array(
				'ad_account_id' => $ad_account_id,
				'has_campaigns' => ! empty( $data['campaigns'] ),
			)
		);
	}
}
