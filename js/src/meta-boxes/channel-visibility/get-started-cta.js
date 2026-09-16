/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getGetStartedUrl } from '~/utils/urls';
import { CHANNEL_VISIBILITY_CONTEXT } from './constants';

const setupUrl = getGetStartedUrl();

/**
 * @event sfw_snapchat_ads_promo_get_started_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'channel-visibility-meta-box'.
 * @property {string} url     The URL the button directs to. Possible value: the onboarding URL.
 */

/**
 * Get Started CTA linking to the Snapchat onboarding entry point.
 *
 * @fires sfw_snapchat_ads_promo_get_started_click When the "Get started" button is clicked.
 *
 * @return {JSX.Element} The Get Started CTA.
 */
const GetStartedCTA = () => {
	return (
		<AppButton
			href={ setupUrl }
			eventName="sfw_snapchat_ads_promo_get_started_click"
			eventProps={ {
				context: CHANNEL_VISIBILITY_CONTEXT,
				url: setupUrl,
			} }
			isSecondary
		>
			{ __( 'Get started', 'snapchat-for-woocommerce' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
