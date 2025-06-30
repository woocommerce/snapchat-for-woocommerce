/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import useSnapchatConnectFlow from './useSnapchatConnectFlow';

/**
 * Renders a card to connect to Snapchat Account.
 *
 * Please note that this component is only used on the Reconnection page.
 * For the onboarding flow, the `SnapchatComboAccountCard` component is used instead.
 */
const ConnectSnapchatAccountCard = () => {
	const pageName = 'reconnect';
	const [ handleConnect, { loading, data } ] =
		useSnapchatConnectFlow( pageName );

	return (
		<AccountCard
			appearance={ APPEARANCE.SNAPCHAT }
			alignIcon="top"
			description={ __( 'Required to sync …', 'snapchat-for-woo' ) }
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					loading={ loading || data }
					eventName="sfw_snapchat_account_connect_button_click"
					eventProps={ {
						context: pageName,
						action: 'authorization',
					} }
					text={ __( 'Connect', 'snapchat-for-woo' ) }
					onClick={ handleConnect }
				/>
			}
		/>
	);
};

export default ConnectSnapchatAccountCard;
