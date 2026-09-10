/**
 * External dependencies
 */
import { Flex, FlexBlock } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { CHANNEL_VISIBILITY_CONTEXT } from './constants';
import GetStartedCTA from './get-started-cta';

/**
 * Promo actions: the Get Started CTA paired with a dismiss control.
 *
 * @param {Object}   props           Component props.
 * @param {Function} props.onDismiss Called when the dismiss button is clicked.
 * @return {JSX.Element} The promo actions row.
 */
const PromoCTA = ( { onDismiss } ) => {
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
					onClick={ onDismiss }
					isTertiary
				>
					{ __( 'Dismiss', 'snapchat-for-woocommerce' ) }
				</AppButton>
			</FlexBlock>
		</Flex>
	);
};

export default PromoCTA;
