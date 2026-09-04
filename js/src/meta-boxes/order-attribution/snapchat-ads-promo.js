/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconExternal from 'gridicons/dist/external';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getOnboardingUrl } from '~/utils/urls';
import snapchatLogoURL from '~/images/logo/snapchat.svg';

/**
 * Renders a single promo banner below the Order Attribution meta box.
 *
 * @param {Object}    props        Component props.
 * @param {string}    props.title  Banner heading.
 * @param {string}    props.body   Banner body copy.
 * @param {JSX.Element} props.cta  The call-to-action button.
 * @return {JSX.Element} The promo banner.
 */
const PromoBanner = ( { title, body, cta } ) => (
	<div className="sfw-order-attribution-promo">
		<div className="sfw-order-attribution-promo__header">
			<img
				className="sfw-order-attribution-promo__logo"
				src={ snapchatLogoURL }
				alt={ __( 'Snapchat', 'snapchat-for-woocommerce' ) }
				width="24"
				height="24"
			/>
			<h3 className="sfw-order-attribution-promo__title">{ title }</h3>
		</div>
		<p className="sfw-order-attribution-promo__body">{ body }</p>
		{ cta }
	</div>
);

/**
 * Renders the Snapchat promo shown below the Order Attribution meta box for
 * Snapchat-attributed orders.
 *
 * Three render states:
 * - Onboarding incomplete: the connect-account (get-started) banner.
 * - Onboarded with no active campaign: the create-campaign banner.
 * - Onboarded with an active campaign: nothing.
 *
 * `hasCampaign` and `createCampaignUrl` come from the localized meta box payload
 * (backend work in SNAPWOO-105). The payload is passed through
 * `wp_localize_script`, which coerces values to strings (`false` becomes `''`,
 * `true` becomes `'1'`), so campaign status is read by truthiness rather than a
 * strict boolean compare. When `hasCampaign` is absent or `createCampaignUrl` is
 * missing, the create-campaign state fails safe by rendering nothing rather than
 * showing a broken CTA.
 *
 * @param {Object}          props                    Component props.
 * @param {boolean|string}  props.onboardingComplete Whether Snapchat onboarding is complete.
 * @param {boolean|string}  [props.hasCampaign]      Whether the merchant has an active campaign.
 * @param {string}          [props.createCampaignUrl] URL to create a campaign in Snapchat Ads Manager.
 * @return {JSX.Element|null} The promo, or null when nothing should render.
 */
const SnapchatAdsPromo = ( {
	onboardingComplete,
	hasCampaign,
	createCampaignUrl,
} ) => {
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
						href={ getOnboardingUrl() }
						eventName="sfw_order_attribution_get_started_button_click"
						text={ __( 'Get started', 'snapchat-for-woocommerce' ) }
					/>
				}
			/>
		);
	}

	if ( hasCampaign !== undefined && ! hasCampaign && createCampaignUrl ) {
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
						icon={ <GridiconExternal /> }
						iconPosition="right"
						eventName="sfw_order_attribution_create_campaign_button_click"
						text={ __(
							'Create campaign',
							'snapchat-for-woocommerce'
						) }
					/>
				}
			/>
		);
	}

	return null;
};

export default SnapchatAdsPromo;
