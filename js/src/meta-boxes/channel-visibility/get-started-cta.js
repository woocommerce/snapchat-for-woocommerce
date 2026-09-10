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

/**
 * Get Started CTA linking to the Snapchat onboarding entry point.
 *
 * @return {JSX.Element} The Get Started CTA.
 */
const GetStartedCTA = () => {
	const setupUrl = getGetStartedUrl();

	return (
		<AppButton
			href={ setupUrl }
			eventName="sfw_snapchat_ads_promo_get_started_click"
			eventProps={ {
				href: setupUrl,
				context: CHANNEL_VISIBILITY_CONTEXT,
			} }
			isSecondary
		>
			{ __( 'Get started', 'snapchat-for-woocommerce' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
