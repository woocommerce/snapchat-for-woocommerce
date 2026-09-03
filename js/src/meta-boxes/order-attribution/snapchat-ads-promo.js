/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getOnboardingUrl } from '~/utils/urls';
import snapchatLogoURL from '~/images/logo/snapchat.svg';

/**
 * Renders the Snapchat connect-account promo shown below the Order Attribution
 * meta box for Snapchat-attributed orders.
 *
 * The promo only renders while onboarding is incomplete. Once the merchant has a
 * connected Snapchat account, nothing is rendered.
 *
 * @param {Object}  props                    Component props.
 * @param {boolean} props.onboardingComplete Whether Snapchat onboarding is complete.
 * @return {JSX.Element|null} The promo, or null when onboarding is complete.
 */
const SnapchatAdsPromo = ( { onboardingComplete } ) => {
	if ( onboardingComplete ) {
		return null;
	}

	return (
		<div className="sfw-order-attribution-promo">
			<div className="sfw-order-attribution-promo__header">
				<img
					className="sfw-order-attribution-promo__logo"
					src={ snapchatLogoURL }
					alt={ __( 'Snapchat', 'snapchat-for-woocommerce' ) }
					width="24"
					height="24"
				/>
				<h3 className="sfw-order-attribution-promo__title">
					{ __(
						'Your next customers are on Snapchat',
						'snapchat-for-woocommerce'
					) }
				</h3>
			</div>
			<p className="sfw-order-attribution-promo__body">
				{ __(
					'Sync your catalog to reach Snapchatters actively discovering new brands and products.',
					'snapchat-for-woocommerce'
				) }
			</p>
			<AppButton
				variant="secondary"
				href={ getOnboardingUrl() }
				eventName="sfw_order_attribution_get_started_button_click"
				text={ __( 'Get started', 'snapchat-for-woocommerce' ) }
			/>
		</div>
	);
};

export default SnapchatAdsPromo;
