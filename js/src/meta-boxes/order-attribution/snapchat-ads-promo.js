/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import snapchatLogoURL from '~/images/logo/snapchat.svg';

/**
 * Renders the Snapchat connect-account promo shown below the Order Attribution
 * meta box for Snapchat-attributed orders.
 *
 * The promo only renders while onboarding is incomplete. Once the merchant has a
 * connected Snapchat account, nothing is rendered.
 *
 * Note: this runs on the classic order edit screen, outside the plugin admin
 * SPA, so the `~/data` store and `snapchatAdsAdminData` are not available here.
 * The CTA therefore uses the core Button; click tracking is wired in SNAPWOO-100.
 *
 * @param {Object}  props                    Component props.
 * @param {boolean} props.onboardingComplete Whether Snapchat onboarding is complete.
 * @param {string}  props.onboardingUrl      URL to the Snapchat onboarding/setup page.
 * @return {JSX.Element|null} The promo, or null when onboarding is complete.
 */
const SnapchatAdsPromo = ( { onboardingComplete, onboardingUrl } ) => {
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
			<Button
				variant="secondary"
				href={ onboardingUrl }
				text={ __( 'Get started', 'snapchat-for-woocommerce' ) }
			/>
		</div>
	);
};

export default SnapchatAdsPromo;
