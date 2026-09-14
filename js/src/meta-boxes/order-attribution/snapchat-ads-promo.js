/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import PromoBanner from './promo-banner';
import { getOnboardingUrl, getCreateCampaignUrl } from '~/utils/urls';

const onboardingUrl = getOnboardingUrl();

/**
 * @event sfw_order_attribution_get_started_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'order-attribution-meta-box'.
 * @property {string} url The URL the button directs to. Possible value: the onboarding URL.
 */

/**
 * @event sfw_order_attribution_create_campaign_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'order-attribution-meta-box'.
 * @property {string} url The URL the button directs to. Possible value: the create-campaign URL.
 */

/**
 * Renders the Snapchat promo within the Order Attribution meta box for
 * Snapchat-attributed orders.
 *
 * @fires sfw_order_attribution_get_started_button_click When the "Get started" button is clicked.
 * @fires sfw_order_attribution_create_campaign_button_click When the "Create campaign" button is clicked.
 *
 * @return {JSX.Element|null} The promo, or null when nothing should render.
 */
const SnapchatAdsPromo = () => {
	const metaBoxData = window.snapchatAdsMetaBoxData;
	const onboardingComplete = metaBoxData?.onboardingComplete;
	const hasCampaign = metaBoxData?.hasCampaign;

	if ( onboardingComplete && hasCampaign ) {
		return null;
	}

	if ( ! onboardingComplete ) {
		return (
			<PromoBanner
				title={ __(
					'Your next customers are on Snapchat',
					'snapchat-for-woocommerce'
				) }
				body={ __(
					'Sync your catalog to reach Snapchatters actively discovering new brands and products.',
					'snapchat-for-woocommerce'
				) }
				cta={
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
				}
			/>
		);
	}

	const createCampaignUrl = getCreateCampaignUrl(
		window.snapchatAdsAdminData?.adAccountId
	);

	if ( ! createCampaignUrl ) {
		return null;
	}

	return (
		<PromoBanner
			title={ __(
				'Get more sales with Snapchat Ads',
				'snapchat-for-woocommerce'
			) }
			body={ __(
				'Launch a Snapchat Ads campaign and get your products discovered by highly engaged communities actively looking for what to buy next.',
				'snapchat-for-woocommerce'
			) }
			cta={
				<AppButton
					variant="secondary"
					href={ createCampaignUrl }
					target="_blank"
					rel="noopener noreferrer"
					icon={ external }
					iconPosition="right"
					eventName="sfw_order_attribution_create_campaign_button_click"
					eventProps={ {
						context: 'order-attribution-meta-box',
						url: createCampaignUrl,
					} }
					text={ __( 'Create campaign', 'snapchat-for-woocommerce' ) }
				/>
			}
		/>
	);
};

export default SnapchatAdsPromo;
