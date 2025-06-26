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
 * Renders a card to connect to Google Account.
 *
 * Please note that this component is only used on the Reconnection page.
 * For the onboarding flow, the `GoogleComboAccountCard` component is used instead.
 *
 * @fires sfw_google_account_connect_button_click with `{ action: 'authorization', context: 'reconnect' }`
 * @fires sfw_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'required-google-permissions', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/setup-and-configuration/#required-google-permissions' }`
 */
const ConnectSnapchatAccountCard = () => {
	const pageName = 'reconnect';
	const [ handleConnect, { loading, data } ] =
		useSnapchatConnectFlow( pageName );

	return (
		<AccountCard
			appearance={ APPEARANCE.SNAPCHAT }
			alignIcon="top"
			description={ __(
				'Required to sync with Google Merchant Center and Google Ads.',
				'snapchat-for-woo'
			) }
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					loading={ loading || data }
					eventName="sfw_google_account_connect_button_click"
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
