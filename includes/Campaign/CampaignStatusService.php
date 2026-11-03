<?php
/**
 * Service for checking whether the connected ad account has any campaigns.
 *
 * The check runs in a background Action Scheduler job and its result is
 * cached in a transient, so the order edit screen only ever reads the cache
 * and is never delayed by a slow or failing WooCommerce Connect Server (WCS)
 * request. The cache is busted on Snapchat connection changes (see
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
	 * The cache is cleared whenever the Snapchat connection changes: on
	 * `onboarding_complete` (fired at the end of the OAuth config-save flow
	 * for connect, reconnect, and ad-account change) and on
	 * `snapchat_disconnected`. On disconnect, any queued background check is
	 * also cancelled. The cached result is tagged with its ad account, so a
	 * change that skips these hooks still never serves a stale result.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( Helper::with_prefix( self::ACTION_HOOK ), array( $this, 'refresh_cache' ) );
		add_action( Helper::with_prefix( 'onboarding_complete' ), array( $this, 'clear_cache' ) );
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
		$cached = $this->get_cached_has_campaigns();

		if ( null !== $cached ) {
			return $cached;
		}

		$this->schedule_refresh();

		return true;
	}

	/**
	 * The cached campaign result for the connected ad account, if there is one.
	 *
	 * Unlike {@see self::has_campaigns()}, this doesn't fall back to true or
	 * schedule a check, so callers can tell "no campaigns" apart from
	 * "not known yet".
	 *
	 * @since n.e.x.t
	 *
	 * @return bool|null Whether the connected ad account has any campaign, or
	 *                   null when there's no cached result for that account.
	 */
	public function get_cached_has_campaigns(): ?bool {
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
	 * Runs via Action Scheduler. The result is cached together with the ad
	 * account ID it was checked for, so a result for a previous account is
	 * never used after the account changes. Failures and unexpected responses
	 * are not cached, so the next cache miss schedules another check.
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
