<?php
/**
 * API module for checking active Snapchat ad campaigns.
 *
 * This class provides an interface for checking, via WooCommerce Connect
 * Server (WCS), whether the connected ad account already has at least one
 * campaign. The result is cached in a transient so the live Ad Partner
 * endpoint isn't hit on every order-edit-screen render, and the cache is
 * busted on Snapchat connection changes (see {@see self::register_hooks()}).
 *
 * @since 1.2.0
 * @package SnapchatForWooCommerce\API\AdPartner
 */

namespace SnapchatForWooCommerce\API\AdPartner;

use SnapchatForWooCommerce\API\AdPartner\BaseAdPartnerApi;
use SnapchatForWooCommerce\Utils\Storage\Transients;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Helper;

/**
 * API module for checking whether the connected ad account has any campaigns.
 *
 * @since 1.2.0
 */
class CampaignApi extends BaseAdPartnerApi {

	/**
	 * Registers the WordPress hooks that bust the campaign cache.
	 *
	 * The cache is cleared whenever the Snapchat connection changes: on
	 * `onboarding_complete` (fired once at the end of the OAuth config-save
	 * flow, which covers connect, reconnect, and ad-account change since they
	 * all share that save path) and on `snapchat_disconnected`.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( Helper::with_prefix( 'onboarding_complete' ), array( $this, 'clear_cache' ) );
		add_action( Helper::with_prefix( 'snapchat_disconnected' ), array( $this, 'clear_cache' ) );
	}

	/**
	 * Clears the cached `has_active_campaigns()` result.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		Transients::delete( TransientDefaults::CAMPAIGN_HAS_ACTIVE );
	}

	/**
	 * Determines whether the given ad account has at least one campaign.
	 *
	 * A campaign counts regardless of its `ACTIVE`/`PAUSED` status (Snap's
	 * Campaign API only defines those two). Deleted campaigns are already
	 * excluded by the endpoint unless `read_deleted_entities=true` is passed,
	 * which this call does not do.
	 *
	 * The result is cached in a transient (see {@see TransientDefaults::CAMPAIGN_HAS_ACTIVE}).
	 * `Store::get()` treats a raw `false` read as "not cached" (indistinguishable
	 * from `get_transient()`'s own false-on-miss/expiry), so the cached value is
	 * stored as the string `'1'`/`'0'` rather than a native bool, with `''` as the
	 * "nothing cached yet" default.
	 *
	 * If the live check fails (expired token, rate limit, network error), this
	 * fails to `true` so the create-campaign banner stays hidden rather than
	 * risking a merchant creating a duplicate campaign. Failures are not cached.
	 *
	 * @since 1.2.0
	 *
	 * @param string $ad_account_id Ad account ID to check.
	 *
	 * @return bool True if the ad account has at least one campaign, or if the
	 *              live check could not be completed.
	 */
	public function has_active_campaigns( string $ad_account_id ): bool {
		if ( '' === $ad_account_id ) {
			return true;
		}

		$cached = Transients::get( TransientDefaults::CAMPAIGN_HAS_ACTIVE );

		if ( '' !== $cached ) {
			return '1' === $cached;
		}

		$response = $this->wcs->proxy_get(
			'/v1/adaccounts/' . rawurlencode( $ad_account_id ) . '/campaigns'
		);

		if ( is_wp_error( $response ) ) {
			return true;
		}

		$data          = $response->get_data();
		$campaigns     = ( isset( $data['campaigns'] ) && is_array( $data['campaigns'] ) ) ? $data['campaigns'] : array();
		$has_campaigns = ! empty( $campaigns );

		Transients::set( TransientDefaults::CAMPAIGN_HAS_ACTIVE, $has_campaigns ? '1' : '0' );

		return $has_campaigns;
	}
}
