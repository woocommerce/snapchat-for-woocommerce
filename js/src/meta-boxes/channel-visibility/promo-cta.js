/**
 * External dependencies
 */
import { Flex, FlexBlock } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import {
	CHANNEL_VISIBILITY_CONTEXT,
	CHANNEL_VISIBILITY_PROMO_KEY,
} from './constants';
import GetStartedCTA from './get-started-cta';

/**
 * @event sfw_snapchat_ads_promo_dismiss_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'channel-visibility-meta-box'.
 */

/**
 * Promo actions: the Get Started CTA paired with a dismiss control.
 *
 * @fires sfw_snapchat_ads_promo_dismiss_click When the "Dismiss" button is clicked.
 *
 * @return {JSX.Element} The promo actions row.
 */
const PromoCTA = () => {
	const { set } = useDispatch( preferencesStore );

	const handleDismiss = () => {
		set( PREFERENCES_STORE_NAMESPACE, CHANNEL_VISIBILITY_PROMO_KEY, true );
	};

	return (
		<Flex gap={ 3 } align="flex-start">
			<FlexBlock>
				<GetStartedCTA />
			</FlexBlock>

			<FlexBlock>
				<AppButton
					eventName="sfw_snapchat_ads_promo_dismiss_click"
					eventProps={ {
						context: CHANNEL_VISIBILITY_CONTEXT,
					} }
					onClick={ handleDismiss }
					isTertiary
				>
					{ __( 'Dismiss', 'snapchat-for-woocommerce' ) }
				</AppButton>
			</FlexBlock>
		</Flex>
	);
};

export default PromoCTA;
