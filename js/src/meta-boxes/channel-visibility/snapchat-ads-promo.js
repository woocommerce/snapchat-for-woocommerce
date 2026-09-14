/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import usePreference from '~/hooks/usePreference';
import snapchatLogoURL from '~/images/logo/snapchat.svg';
import { recordSfwEvent } from '~/utils/tracks';
import {
	CHANNEL_VISIBILITY_CONTEXT,
	CHANNEL_VISIBILITY_PROMO_KEY,
} from './constants';
import GetStartedCTA from './get-started-cta';
import PromoCTA from './promo-cta';
import './snapchat-ads-promo.scss';

/**
 * @event sfw_snapchat_ads_promo_shown
 * @property {string} context Indicates from which page the promo was shown. Possible value: 'channel-visibility-meta-box'.
 */

/**
 * Snapchat Ads promo shown in the Channel visibility widget when onboarding is incomplete.
 *
 * @fires sfw_snapchat_ads_promo_shown When the promo first renders while onboarding is incomplete.
 *
 * @return {JSX.Element|null} The promo, or null once onboarding is complete.
 */
const SnapchatAdsPromo = () => {
	const { onboardingComplete = false } = window.snapchatAdsMetaBoxData || {};
	const isDismissed = usePreference( CHANNEL_VISIBILITY_PROMO_KEY );
	const hasTrackedRef = useRef( false );

	useEffect( () => {
		if ( onboardingComplete ) {
			return;
		}

		if ( ! hasTrackedRef.current ) {
			recordSfwEvent( 'sfw_snapchat_ads_promo_shown', {
				context: CHANNEL_VISIBILITY_CONTEXT,
			} );
			hasTrackedRef.current = true;
		}
	}, [ onboardingComplete ] );

	if ( onboardingComplete ) {
		return null;
	}

	return (
		<Flex className="sfw-channel-visibility" direction="column" gap={ 4 }>
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="flex-start">
					<FlexItem>
						<img
							className="sfw-channel-visibility__logo"
							src={ snapchatLogoURL }
							alt={ __(
								'Snapchat Logo',
								'snapchat-for-woocommerce'
							) }
							width={ 16 }
							height={ 16 }
						/>
					</FlexItem>
					<FlexItem>
						{ __( 'Snapchat', 'snapchat-for-woocommerce' ) }
					</FlexItem>
					{ isDismissed && (
						<FlexItem className="sfw-channel-visibility__get-started--is-dismissed">
							<GetStartedCTA />
						</FlexItem>
					) }
				</Flex>
			</FlexBlock>

			{ ! isDismissed && (
				<Flex
					className="sfw-channel-visibility__content"
					direction="column"
					gap={ 3 }
				>
					<FlexBlock>
						<h3 className="sfw-channel-visibility__title">
							{ __(
								'Get your products on Snapchat',
								'snapchat-for-woocommerce'
							) }
						</h3>
					</FlexBlock>
					<FlexBlock>
						<p>
							{ __(
								'Sync your products to reach customers as they watch, browse and shop across Snapchat',
								'snapchat-for-woocommerce'
							) }
						</p>
					</FlexBlock>
					<FlexBlock>
						<PromoCTA />
					</FlexBlock>
				</Flex>
			) }
		</Flex>
	);
};

export default SnapchatAdsPromo;
