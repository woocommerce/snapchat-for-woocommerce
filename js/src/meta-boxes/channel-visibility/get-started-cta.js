/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import {
	REFERRER_TYPE_IN_PRODUCT_PLACEMENT,
	withReferrer,
} from '~/utils/tracks';
import { getGetStartedUrl } from '~/utils/urls';
import { CHANNEL_VISIBILITY_CONTEXT } from './constants';

const setupUrl = withReferrer( getGetStartedUrl(), CHANNEL_VISIBILITY_CONTEXT );

/**
 * Snapchat Ads promo "Get started" button is clicked.
 *
 * @event sfw_ads_promo_get_started_click
 * @property {string} context       Context of the Snapchat Ads promo. Possible values: 'channel-visibility-meta-box', 'order-attribution-meta-box'.
 * @property {string} href          URL of the "Get started" button, including the referrer params.
 * @property {string} referrer_type Type of the referring surface. Possible value: 'in_product_placement'.
 * @property {string} referrer_id   Identifier of the referring placement. Same value as `context`.
 */

/**
 * Get Started CTA linking to the Snapchat onboarding entry point.
 *
 * @fires sfw_ads_promo_get_started_click with `{ context: 'channel-visibility-meta-box', href: 'admin.php?page=wc-admin&path=%2Fsnapchat%2Fstart&referrer_type=in_product_placement&referrer_id=channel-visibility-meta-box', referrer_type: 'in_product_placement', referrer_id: 'channel-visibility-meta-box' }`.
 *
 * @return {JSX.Element} The Get Started CTA.
 */
const GetStartedCTA = () => {
	return (
		<AppButton
			href={ setupUrl }
			eventName="sfw_ads_promo_get_started_click"
			eventProps={ {
				context: CHANNEL_VISIBILITY_CONTEXT,
				href: setupUrl,
				referrer_type: REFERRER_TYPE_IN_PRODUCT_PLACEMENT,
				referrer_id: CHANNEL_VISIBILITY_CONTEXT,
			} }
			isSecondary
		>
			{ __( 'Get started', 'snapchat-for-woocommerce' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
