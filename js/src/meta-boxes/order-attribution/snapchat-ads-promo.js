/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import PromoBanner from './promo-banner';
import useSnapchatAccountDetails from '~/hooks/useSnapchatAccountDetails';
import {
	recordSfwEvent,
	REFERRER_TYPE_IN_PRODUCT_PLACEMENT,
	withReferrer,
} from '~/utils/tracks';
import { getOnboardingUrl, getCreateCampaignUrl } from '~/utils/urls';
import { ORDER_ATTRIBUTION_CONTEXT } from './constants';

const onboardingUrl = withReferrer(
	getOnboardingUrl(),
	ORDER_ATTRIBUTION_CONTEXT
);

/**
 * Snapchat Ads promo is shown.
 *
 * @event sfw_ads_promo_shown
 * @property {string} context Context of the Snapchat Ads promo. Possible values: 'channel-visibility-meta-box', 'order-attribution-meta-box'.
 */

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
 * Snapchat Ads promo "Create campaign" button is clicked.
 *
 * @event sfw_ads_promo_create_campaign_click
 * @property {string} context       Context of the Snapchat Ads promo. Possible value: 'order-attribution-meta-box'.
 * @property {string} href          URL of the "Create campaign" button, including the referrer params.
 * @property {string} referrer_type Type of the referring surface. Possible value: 'in_product_placement'.
 * @property {string} referrer_id   Identifier of the referring placement. Same value as `context`.
 */

/**
 * Renders the Snapchat promo within the Order Attribution meta box for
 * Snapchat-attributed orders.
 *
 * @fires sfw_ads_promo_shown with `{ context: 'order-attribution-meta-box' }`.
 * @fires sfw_ads_promo_get_started_click with `{ context: 'order-attribution-meta-box', href: 'admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup&referrer_type=in_product_placement&referrer_id=order-attribution-meta-box', referrer_type: 'in_product_placement', referrer_id: 'order-attribution-meta-box' }`.
 * @fires sfw_ads_promo_create_campaign_click with `{ context: 'order-attribution-meta-box', href: 'https://ads.snapchat.com/<ad_account_id>/create-campaign?referrer_type=in_product_placement&referrer_id=order-attribution-meta-box', referrer_type: 'in_product_placement', referrer_id: 'order-attribution-meta-box' }`.
 *
 * @return {JSX.Element|null} The promo, or null when nothing should render.
 */
const SnapchatAdsPromo = () => {
	const metaBoxData = window.snapchatAdsMetaBoxData;
	const onboardingComplete = metaBoxData?.onboardingComplete;
	const hasCampaign = metaBoxData?.hasCampaign;

	const { ad_acc_id: adAccountId } = useSnapchatAccountDetails();
	const hasTrackedRef = useRef( false );

	const createCampaignUrl = getCreateCampaignUrl( adAccountId );
	const showCreateCampaign =
		onboardingComplete && ! hasCampaign && Boolean( createCampaignUrl );
	const shouldShowPromo = ! onboardingComplete || showCreateCampaign;

	useEffect( () => {
		if ( ! hasTrackedRef.current && shouldShowPromo ) {
			recordSfwEvent( 'sfw_ads_promo_shown', {
				context: ORDER_ATTRIBUTION_CONTEXT,
			} );
			hasTrackedRef.current = true;
		}
	}, [ shouldShowPromo ] );

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
						eventName="sfw_ads_promo_get_started_click"
						eventProps={ {
							context: ORDER_ATTRIBUTION_CONTEXT,
							href: onboardingUrl,
							referrer_type: REFERRER_TYPE_IN_PRODUCT_PLACEMENT,
							referrer_id: ORDER_ATTRIBUTION_CONTEXT,
						} }
						text={ __( 'Get started', 'snapchat-for-woocommerce' ) }
					/>
				}
			/>
		);
	}

	if ( ! showCreateCampaign ) {
		return null;
	}

	const createCampaignHref = withReferrer(
		createCampaignUrl,
		ORDER_ATTRIBUTION_CONTEXT
	);

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
					href={ createCampaignHref }
					target="_blank"
					rel="noopener noreferrer"
					icon={ external }
					iconPosition="right"
					eventName="sfw_ads_promo_create_campaign_click"
					eventProps={ {
						context: ORDER_ATTRIBUTION_CONTEXT,
						href: createCampaignHref,
						referrer_type: REFERRER_TYPE_IN_PRODUCT_PLACEMENT,
						referrer_id: ORDER_ATTRIBUTION_CONTEXT,
					} }
					text={ __( 'Create campaign', 'snapchat-for-woocommerce' ) }
				/>
			}
		/>
	);
};

export default SnapchatAdsPromo;
