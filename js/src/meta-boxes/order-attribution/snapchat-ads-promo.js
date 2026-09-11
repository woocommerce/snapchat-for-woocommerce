/**
 * External dependencies
 */
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getOnboardingUrl } from '~/utils/urls';
import snapchatLogoURL from '~/images/logo/snapchat.svg';

const onboardingUrl = getOnboardingUrl();

/**
 * @event sfw_order_attribution_get_started_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'order-attribution-meta-box'.
 * @property {string} url The URL the button directs to. Possible value: the onboarding URL.
 */

/**
 * Renders the Snapchat connect-account promo within the Order Attribution meta
 * box, while onboarding is incomplete.
 *
 * @fires sfw_order_attribution_get_started_button_click When the "Get started" button is clicked.
 *
 * @return {JSX.Element|null} The promo, or null when onboarding is complete.
 */
const SnapchatAdsPromo = () => {
	if ( window.snapchatAdsMetaBoxData.onboardingComplete ) {
		return null;
	}

	return (
		<Flex
			direction="column"
			className="sfw-order-attribution-promo"
			gap={ 3 }
		>
			<FlexBlock>
				<Flex align="center" gap={ 2 }>
					<FlexItem>
						<img
							src={ snapchatLogoURL }
							alt={ __(
								'Snapchat logo',
								'snapchat-for-woocommerce'
							) }
							width="24"
							height="24"
						/>
					</FlexItem>
					<FlexBlock>
						<h3 className="sfw-order-attribution-promo__title">
							{ __(
								'Your next customers are on Snapchat',
								'snapchat-for-woocommerce'
							) }
						</h3>
					</FlexBlock>
				</Flex>
			</FlexBlock>

			<FlexBlock>
				<p className="sfw-order-attribution-promo__body">
					{ __(
						'Sync your catalog to reach Snapchatters actively discovering new brands and products.',
						'snapchat-for-woocommerce'
					) }
				</p>
			</FlexBlock>

			<FlexBlock>
				<AppButton
					variant="secondary"
					href={ onboardingUrl }
					eventName="sfw_order_attribution_get_started_button_click"
					eventProps={ {
						context: 'order-attribution-meta-box',
						url: onboardingUrl,
					} }
					text={ __( 'Get started', 'snapchat-for-woocommerce' ) }
				/>
			</FlexBlock>
		</Flex>
	);
};

export default SnapchatAdsPromo;
